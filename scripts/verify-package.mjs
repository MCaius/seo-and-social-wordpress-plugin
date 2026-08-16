import { createHash } from "node:crypto";
import { mkdir, readFile, rm, writeFile } from "node:fs/promises";
import { dirname, join, posix } from "node:path";
import { fileURLToPath } from "node:url";
import { inflateRawSync } from "node:zlib";

const rootDir = dirname(dirname(fileURLToPath(import.meta.url)));
const pluginSlug = "seo-and-social";
const distDir = join(rootDir, "dist");
const zipPath = join(distDir, `${pluginSlug}.zip`);
const manifestPath = join(distDir, `${pluginSlug}.manifest.json`);
const extractionDir = join(distDir, "package");
const requiredFiles = new Set([
  `${pluginSlug}/readme.txt`,
  `${pluginSlug}/seo-and-social.php`,
  `${pluginSlug}/uninstall.php`,
]);
const allowedExtensions = new Set([".php", ".css", ".js", ".mo"]);
const bannedSegments = new Set([
  ".git",
  ".github",
  "dist",
  "docs",
  "node_modules",
  "scripts",
  "tests",
  "vendor",
]);

function fail(message) {
  throw new Error(`Package verification failed: ${message}`);
}

function crc32(buffer) {
  let crc = 0xffffffff;

  for (const byte of buffer) {
    crc ^= byte;

    for (let index = 0; index < 8; index += 1) {
      crc = (crc >>> 1) ^ (0xedb88320 & -(crc & 1));
    }
  }

  return (crc ^ 0xffffffff) >>> 0;
}

function findEndOfCentralDirectory(zip) {
  for (let offset = zip.length - 22; offset >= Math.max(0, zip.length - 65557); offset -= 1) {
    if (zip.readUInt32LE(offset) === 0x06054b50) {
      return offset;
    }
  }

  fail("end-of-central-directory record is missing");
}

function readEntries(zip) {
  const endOffset = findEndOfCentralDirectory(zip);
  const entryCount = zip.readUInt16LE(endOffset + 10);
  const centralSize = zip.readUInt32LE(endOffset + 12);
  const centralOffset = zip.readUInt32LE(endOffset + 16);
  const entries = [];
  let offset = centralOffset;

  if (centralOffset + centralSize > endOffset) {
    fail("central directory points outside the archive");
  }

  for (let index = 0; index < entryCount; index += 1) {
    if (zip.readUInt32LE(offset) !== 0x02014b50) {
      fail(`invalid central-directory entry ${index + 1}`);
    }

    const method = zip.readUInt16LE(offset + 10);
    const checksum = zip.readUInt32LE(offset + 16);
    const compressedSize = zip.readUInt32LE(offset + 20);
    const uncompressedSize = zip.readUInt32LE(offset + 24);
    const nameLength = zip.readUInt16LE(offset + 28);
    const extraLength = zip.readUInt16LE(offset + 30);
    const commentLength = zip.readUInt16LE(offset + 32);
    const localOffset = zip.readUInt32LE(offset + 42);
    const name = zip.subarray(offset + 46, offset + 46 + nameLength).toString("utf8");

    entries.push({ name, method, checksum, compressedSize, uncompressedSize, localOffset });
    offset += 46 + nameLength + extraLength + commentLength;
  }

  if (offset !== centralOffset + centralSize) {
    fail("central-directory size does not match its entries");
  }

  return entries;
}

function validateEntryName(name) {
  if (!name.startsWith(`${pluginSlug}/`) || name.includes("\\") || name.startsWith("/") || name.endsWith("/")) {
    fail(`unsafe or unexpected archive path: ${name}`);
  }

  const normalized = posix.normalize(name);
  const segments = normalized.split("/");

  if (normalized !== name || segments.includes("..") || segments.some((segment) => bannedSegments.has(segment))) {
    fail(`prohibited archive path: ${name}`);
  }

  const filename = posix.basename(name);
  const extension = filename.includes(".") ? filename.slice(filename.lastIndexOf(".")) : "";

  if (filename !== "readme.txt" && !allowedExtensions.has(extension)) {
    fail(`prohibited runtime file type: ${name}`);
  }

  if (filename.startsWith("phpunit") || filename.startsWith(".wp-env") || extension === ".md") {
    fail(`development file found in archive: ${name}`);
  }
}

function extractEntry(zip, entry) {
  const offset = entry.localOffset;

  if (zip.readUInt32LE(offset) !== 0x04034b50) {
    fail(`invalid local header for ${entry.name}`);
  }

  const nameLength = zip.readUInt16LE(offset + 26);
  const extraLength = zip.readUInt16LE(offset + 28);
  const dataOffset = offset + 30 + nameLength + extraLength;
  const compressed = zip.subarray(dataOffset, dataOffset + entry.compressedSize);
  let content;

  if (entry.method === 0) {
    content = compressed;
  } else if (entry.method === 8) {
    content = inflateRawSync(compressed);
  } else {
    fail(`unsupported compression method for ${entry.name}`);
  }

  if (content.length !== entry.uncompressedSize || crc32(content) !== entry.checksum) {
    fail(`size or CRC mismatch for ${entry.name}`);
  }

  return content;
}

const [zip, manifestRaw] = await Promise.all([readFile(zipPath), readFile(manifestPath, "utf8")]);
const manifest = JSON.parse(manifestRaw);
const digest = createHash("sha256").update(zip).digest("hex");

if (manifest.file !== `dist/${pluginSlug}.zip`) {
  fail("manifest points to an unexpected archive");
}

if (manifest.bytes !== zip.length || manifest.sha256 !== digest) {
  fail("manifest size or SHA256 does not match the ZIP");
}

const entries = readEntries(zip);
const entryNames = entries.map((entry) => entry.name);
const uniqueNames = new Set(entryNames);

if (uniqueNames.size !== entryNames.length) {
  fail("archive contains duplicate paths");
}

for (const entry of entries) {
  validateEntryName(entry.name);
}

for (const requiredFile of requiredFiles) {
  if (!uniqueNames.has(requiredFile)) {
    fail(`required runtime file is missing: ${requiredFile}`);
  }
}

const manifestNames = Array.isArray(manifest.includedFiles) ? [...manifest.includedFiles].sort() : [];
const sortedEntryNames = [...entryNames].sort();

if (JSON.stringify(manifestNames) !== JSON.stringify(sortedEntryNames)) {
  fail("manifest file list does not match the ZIP central directory");
}

await rm(extractionDir, { recursive: true, force: true });

for (const entry of entries) {
  const destination = join(extractionDir, ...entry.name.split("/"));
  await mkdir(dirname(destination), { recursive: true });
  await writeFile(destination, extractEntry(zip, entry));
}

console.log(`Verified ${zipPath}`);
console.log(`Validated and extracted ${entries.length} runtime files`);
console.log(`SHA256 ${digest}`);
