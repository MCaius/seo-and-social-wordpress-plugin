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

  $(document).on("submit", "form", function () {
    if (window.tinyMCE && tinyMCE.triggerSave) {
      tinyMCE.triggerSave();
    }
  });

  document.querySelectorAll(".sas-faq-row").forEach(updateFaqRowToggle);
})(jQuery);
