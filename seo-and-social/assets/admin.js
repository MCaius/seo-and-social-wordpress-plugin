(function ($) {
  function uniqueIndex() {
    return String(Date.now()) + String(Math.floor(Math.random() * 10000));
  }

  function updateFaqRowToggle(row) {
    const toggle = row.querySelector(".sas-faq-row-toggle");

    if (!toggle) {
      return;
    }

    toggle.textContent = row.classList.contains("is-collapsed") ? "Open" : "Close";
  }

  function initializeFaqEditorTestIds(editor) {
	const codeTab = document.getElementById(`${editor.id}-html`);
	const toolbar = document.getElementById(`qt_${editor.id}_toolbar`);

	if (codeTab) {
	  codeTab.dataset.testid = "sas-faq-code-tab";
	}

	if (toolbar) {
	  toolbar.dataset.testid = "sas-faq-code-toolbar";

	  Array.from(toolbar.children).forEach(function (button) {
		if (button.tagName === "INPUT") {
		  button.dataset.testid = "sas-faq-code-button";
		}
	  });
	}
  }

  function initializeFaqEditor(editor) {
    if (!editor || editor.dataset.editorInitialized === "true" || !window.wp || !wp.editor) {
      return;
    }

    wp.editor.initialize(editor.id, {
      mediaButtons: false,
      tinymce: {
        wpautop: false,
        forced_root_block: "p",
        toolbar1: "formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo",
        toolbar2: "",
        block_formats: "Paragraph=p;Heading 3=h3;Heading 4=h4",
      },
      quicktags: {
        buttons: "strong,em,link,block,ul,ol,li,close",
      },
    });

    editor.dataset.editorInitialized = "true";
	initializeFaqEditorTestIds(editor);
	window.setTimeout(function () {
	  initializeFaqEditorTestIds(editor);
	}, 0);
  }

  function initializeMetaBoxTestIds() {
    const metaBoxes = [
      ["sas-seo-meta-box", "sas-toggle-seo-meta-box"],
      ["sas-faq-meta-box", "sas-toggle-faq-meta-box"],
    ];

    metaBoxes.forEach(function ([contentTestId, toggleTestId]) {
      const content = document.querySelector(`[data-testid="${contentTestId}"]`);
      const postbox = content ? content.closest(".postbox") : null;
      const toggle = postbox ? postbox.querySelector(".handlediv") : null;
      const metaBoxesLiner = postbox ? postbox.closest(".edit-post-meta-boxes-main__liner") : null;
      const metaBoxesPresenter = metaBoxesLiner ? metaBoxesLiner.previousElementSibling : null;
      const metaBoxesToggle = metaBoxesPresenter ? metaBoxesPresenter.querySelector('button[aria-expanded]') : null;

      if (toggle) {
        toggle.dataset.testid = toggleTestId;
      }

      if (metaBoxesToggle) {
        metaBoxesToggle.dataset.testid = "sas-toggle-meta-boxes-area";
      }
    });
  }

  function isAbsoluteHttpUrl(value) {
    const normalizedValue = value.trim();

    if (!/^https?:\/\/[^/?#\s]+(?:[/?#]|$)/i.test(normalizedValue)) {
      return false;
    }

    try {
      const url = new URL(normalizedValue);

      return (url.protocol === "http:" || url.protocol === "https:") && Boolean(url.hostname);
    } catch {
      return false;
    }
  }

  function validateSchemaUrlRow(row) {
    const type = row.querySelector('[data-testid="sas-extra-schema-type"]');
    const value = row.querySelector('[data-testid="sas-extra-schema-value"]');

    if (!type || !value) {
      return true;
    }

    value.setCustomValidity("");

    if (type.value === "url" && value.value.trim() && !isAbsoluteHttpUrl(value.value)) {
      value.setCustomValidity(sasAdmin.invalidHttpUrl);
      return false;
    }

    return true;
  }

  function schemaRowSignature(row) {
    const key = row.querySelector('[data-testid="sas-extra-schema-key"]')?.value
      .trim()
      .replace(/[^A-Za-z0-9_\-:@.]/g, "");
    const type = row.querySelector('[data-testid="sas-extra-schema-type"]')?.value || "text";
    const valueElement = row.querySelector('[data-testid="sas-extra-schema-value"]');
    let value = valueElement?.value.trim() || "";

    if (!key || !value) {
      return "";
    }

    if (type === "list") {
      value = value.split(/\r\n|\r|\n/).map((item) => item.trim()).filter(Boolean).join("\n");
    } else if (type === "json") {
      try {
        value = JSON.stringify(JSON.parse(value));
      } catch {
        return "";
      }
    }

    return JSON.stringify([key, type, value]);
  }

  function findDuplicateSchemaRow(form) {
    const seen = new Set();
    let firstDuplicate = null;

    form.querySelectorAll(".sas-schema-row").forEach(function (row) {
      const value = row.querySelector('[data-testid="sas-extra-schema-value"]');
      const signature = schemaRowSignature(row);

      if (!value || !signature) {
        return;
      }

      if (seen.has(signature)) {
        value.setCustomValidity(sasAdmin.duplicateSchemaProperty);
        firstDuplicate = firstDuplicate || row;
        return;
      }

      seen.add(signature);
    });

    return firstDuplicate;
  }

  function validateAbsoluteUrlInput(input) {
    input.setCustomValidity("");

    if (input.value.trim() && !isAbsoluteHttpUrl(input.value)) {
      input.setCustomValidity(sasAdmin.invalidHttpUrl);
    }
  }

  function findDuplicateValue(rows, signatureForRow, fieldForRow, message) {
    const seen = new Set();

    rows.forEach(function (row) {
      const signature = signatureForRow(row);
      const field = fieldForRow(row);

      if (!signature || !field) {
        return;
      }

      if (seen.has(signature)) {
        field.setCustomValidity(message);
        return;
      }

      seen.add(signature);
    });
  }

  function validateSettingsRows(form) {
    const schemaRows = Array.from(form.querySelectorAll(".sas-schema-row"));
    const socialRows = Array.from(form.querySelectorAll('[data-testid="sas-extra-social-row"]'));
    const recommendedRows = Array.from(form.querySelectorAll('[data-testid="sas-llms-recommended-page-row"]'));

    schemaRows.forEach(validateSchemaUrlRow);
    socialRows.forEach(function (row) {
      validateAbsoluteUrlInput(row.querySelector('[data-testid="sas-extra-social-url"]'));
      row.querySelector('[data-testid="sas-extra-social-key"]').setCustomValidity("");
    });
    recommendedRows.forEach(function (row) {
      validateAbsoluteUrlInput(row.querySelector('[data-testid="sas-llms-recommended-page-url"]'));
    });

    findDuplicateSchemaRow(form);
    findDuplicateValue(
      socialRows,
      (row) => row.querySelector('[data-testid="sas-extra-social-key"]').value.trim().toLowerCase().replace(/\s+/g, "-"),
      (row) => row.querySelector('[data-testid="sas-extra-social-key"]'),
      sasAdmin.duplicateSocialKey
    );
    findDuplicateValue(
      recommendedRows,
      (row) => row.querySelector('[data-testid="sas-llms-recommended-page-url"]').value.trim(),
      (row) => row.querySelector('[data-testid="sas-llms-recommended-page-url"]'),
      sasAdmin.duplicateRecommendedUrl
    );

    const invalidField = Array.from(form.querySelectorAll("input, select, textarea")).find(function (field) {
      return field.validity && field.validity.customError;
    });

    return invalidField ? { field: invalidField, row: invalidField.closest(".sas-row") } : null;
  }

  $(document).on("click", ".sas-info-button", function () {
    const button = $(this);
    const panel = $("#" + button.attr("aria-controls"));
    const expanded = button.attr("aria-expanded") === "true";

    button.attr("aria-expanded", expanded ? "false" : "true");
    panel.prop("hidden", expanded);
  });

  $(document).on("click", "[data-sas-add-row]", function () {
    const button = $(this);
    const repeater = button.closest("[data-sas-repeater]");
    const rows = repeater.find("[data-sas-rows]").first();
    const template = $("#" + button.data("template")).html();

    if (!template) {
      return;
    }

    rows.append(template.replaceAll("__INDEX__", uniqueIndex()));

    const addedRow = rows.children().last().get(0);

    if (addedRow && addedRow.classList.contains("sas-faq-row")) {
      addedRow.classList.remove("is-collapsed");
      updateFaqRowToggle(addedRow);
      initializeFaqEditor(addedRow.querySelector(".sas-faq-answer-editor"));
    }
  });

  $(document).on("click", "[data-sas-remove-row]", function () {
    const row = $(this).closest(".sas-row").get(0);
    const editor = row ? row.querySelector(".sas-faq-answer-editor") : null;

    if (editor && editor.dataset.editorInitialized === "true" && window.wp && wp.editor) {
      wp.editor.remove(editor.id);
    }

    if (row) {
      row.remove();
    }
  });

  $(document).on("click", "[data-sas-toggle-faq-row]", function () {
    const row = $(this).closest(".sas-faq-row").get(0);

    if (!row) {
      return;
    }

    row.classList.toggle("is-collapsed");
    updateFaqRowToggle(row);

    if (!row.classList.contains("is-collapsed")) {
      initializeFaqEditor(row.querySelector(".sas-faq-answer-editor"));
    }
  });

  $(document).on("input", ".sas-faq-title-input", function () {
    const row = $(this).closest(".sas-faq-row").get(0);
    const title = row ? row.querySelector(".sas-faq-row-title") : null;

    if (title) {
      title.textContent = this.value.trim() || "New question";
    }
  });

  $(document).on("input change", ".sas-schema-row input, .sas-schema-row select, .sas-schema-row textarea, [data-testid=\"sas-extra-social-row\"] input, [data-testid=\"sas-llms-recommended-page-row\"] input", function () {
    const form = this.closest("form");

    if (form) {
      validateSettingsRows(form);
    }
  });

  $(document).on("click", "[data-sas-media-url-target]", function () {
    const button = $(this);
    const urlTarget = $("#" + button.data("sasMediaUrlTarget"));
    const idTargetName = button.data("sasMediaIdTarget");
    const idTarget = idTargetName ? $("#" + idTargetName) : null;

    const frame = wp.media({
      title: sasAdmin.chooseImage,
      button: { text: sasAdmin.useImage },
      multiple: false,
    });

    frame.on("select", function () {
      const attachment = frame.state().get("selection").first().toJSON();
      const url = attachment.url || "";

      urlTarget.val(url);

      if (idTarget && idTarget.length) {
        idTarget.val(attachment.id || "");
      }
    });

    frame.open();
  });

  $(document).on("input", "[data-sas-media-linked-id]", function () {
    const idTarget = $("#" + this.dataset.sasMediaLinkedId);

    if (idTarget.length) {
      idTarget.val("");
    }
  });

  $(document).on("click", "[data-sas-confirm-delete]", function (event) {
    const confirmed = window.confirm(
      sasAdmin.confirmDeleteAllData
    );

    if (!confirmed) {
      event.preventDefault();
    }
  });

  $(document).on("click", "[data-sas-confirm-delete-og-images]", function (event) {
    const confirmed = window.confirm(
      sasAdmin.confirmDeleteOgImages
    );

    if (!confirmed) {
      event.preventDefault();
    }
  });

  $(document).on("submit", "form", function (event) {
    if (this.classList.contains("sas-settings-form")) {
      const invalid = validateSettingsRows(this);

      if (invalid) {
        event.preventDefault();

        invalid.field.reportValidity();
        invalid.field.focus();
        return;
      }
    }

    if (window.tinyMCE && tinyMCE.triggerSave) {
      tinyMCE.triggerSave();
    }
  });

  document.querySelectorAll(".sas-faq-row").forEach(updateFaqRowToggle);
  initializeMetaBoxTestIds();
  window.setTimeout(initializeMetaBoxTestIds, 250);
  window.setTimeout(initializeMetaBoxTestIds, 1000);
  window.setTimeout(initializeMetaBoxTestIds, 2000);
})(jQuery);
