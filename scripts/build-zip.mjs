import { createHash } from "node:crypto";
import { mkdir, readdir, readFile, rm, stat, writeFile } from "node:fs/promises";
import { basename, dirname, join, relative } from "node:path";
import { fileURLToPath } from "node:url";
import { deflateRawSync } from "node:zlib";

const rootDir = dirname(dirname(fileURLToPath(import.meta.url)));
const pluginSlug = "seo-and-social";
const pluginDir = join(rootDir, pluginSlug);
const distDir = join(rootDir, "dist");
const zipPath = join(distDir, `${pluginSlug}.zip`);
const allowedExtensions = new Set([".php", ".css", ".js", ".mo"]);
const extraAllowedFiles = new Set(["readme.txt"]);

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

function dosDateTime(date) {
  const year = Math.max(date.getFullYear(), 1980);
  const dosTime = (date.getHours() << 11) | (date.getMinutes() << 5) | Math.floor(date.getSeconds() / 2);
  const dosDate = ((year - 1980) << 9) | ((date.getMonth() + 1) << 5) | date.getDate();

  return { dosTime, dosDate };
}

function uint16(value) {
  const buffer = Buffer.alloc(2);
  buffer.writeUInt16LE(value);
  return buffer;
}

function uint32(value) {
  const buffer = Buffer.alloc(4);
  buffer.writeUInt32LE(value);
  return buffer;
}

function shouldInclude(filePath) {
  const name = basename(filePath);
  const extension = name.includes(".") ? name.slice(name.lastIndexOf(".")) : "";

  return allowedExtensions.has(extension) || extraAllowedFiles.has(name);
}

async function collectFiles(directory) {
  const entries = await readdir(directory, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const fullPath = join(directory, entry.name);

    if (entry.isDirectory()) {
      files.push(...(await collectFiles(fullPath)));
      continue;
    }

    if (entry.isFile() && shouldInclude(fullPath)) {
      files.push(fullPath);
    }
  }

  return files.sort();
}

async function createZip(files) {
  const localParts = [];
  const centralParts = [];
  let offset = 0;

  for (const filePath of files) {
    const relativePath = `${pluginSlug}/${relative(pluginDir, filePath).replaceAll("\\", "/")}`;
    const nameBuffer = Buffer.from(relativePath);
    const content = await readFile(filePath);
    const compressed = deflateRawSync(content, { level: 9 });
    const checksum = crc32(content);
    const fileStat = await stat(filePath);
    const { dosTime, dosDate } = dosDateTime(fileStat.mtime);

    const localHeader = Buffer.concat([
      uint32(0x04034b50),
      uint16(20),
      uint16(0),
      uint16(8),
      uint16(dosTime),
      uint16(dosDate),
      uint32(checksum),
      uint32(compressed.length),
      uint32(content.length),
      uint16(nameBuffer.length),
      uint16(0),
      nameBuffer,
    ]);

    localParts.push(localHeader, compressed);

    const centralHeader = Buffer.concat([
      uint32(0x02014b50),
      uint16(20),
      uint16(20),
      uint16(0),
      uint16(8),
      uint16(dosTime),
      uint16(dosDate),
      uint32(checksum),
      uint32(compressed.length),
      uint32(content.length),
      uint16(nameBuffer.length),
      uint16(0),
      uint16(0),
      uint16(0),
      uint16(0),
      uint32(0),
      uint32(offset),
      nameBuffer,
    ]);

    centralParts.push(centralHeader);
    offset += localHeader.length + compressed.length;
  }

  const centralDirectory = Buffer.concat(centralParts);
  const endRecord = Buffer.concat([
    uint32(0x06054b50),
    uint16(0),
    uint16(0),
    uint16(files.length),
    uint16(files.length),
    uint32(centralDirectory.length),
    uint32(offset),
    uint16(0),
  ]);

  return Buffer.concat([...localParts, centralDirectory, endRecord]);
}

await rm(distDir, { recursive: true, force: true });
await mkdir(distDir, { recursive: true });

const files = await collectFiles(pluginDir);
const zip = await createZip(files);
await writeFile(zipPath, zip);

const digest = createHash("sha256").update(zip).digest("hex");
const manifest = {
  file: `dist/${pluginSlug}.zip`,
  bytes: zip.length,
  sha256: digest,
  includedFiles: files.map((filePath) => `${pluginSlug}/${relative(pluginDir, filePath).replaceAll("\\", "/")}`),
};

await writeFile(join(distDir, `${pluginSlug}.manifest.json`), `${JSON.stringify(manifest, null, 2)}\n`);

console.log(`Created ${zipPath}`);
console.log(`Included ${files.length} files`);
console.log(`SHA256 ${digest}`);
