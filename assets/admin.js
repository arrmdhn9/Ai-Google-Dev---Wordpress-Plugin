jQuery(document).ready(function ($) {
  var selectedImages = [];

  // Media Uploader
  $("#gpw_upload_btn").on("click", function (e) {
    var frame = wp.media({ title: "Pilih Gambar Tutorial", multiple: true });
    frame.on("select", function () {
      var attachments = frame.state().get("selection").toJSON();
      $("#gpw_img_preview").html("");
      selectedImages = [];
      attachments.forEach(function (img) {
        selectedImages.push(img.id);
        $("#gpw_img_preview").append(
          '<img src="' +
            img.url +
            '" style="width:50px;height:50px;margin:2px;object-fit:cover;">',
        );
      });
    });
    frame.open();
  });

  // AJAX Generate
  $("#gpw_gen_btn").on("click", function () {
    var btn = $(this);
    var prompt = $("#gpw_prompt").val();

    if (!prompt) {
      alert("Isi prompt dulu bos!");
      return;
    }

    btn.prop("disabled", true).text("Thinking...");
    $("#gpw_status").html("⏳ Gemini sedang menulis...");

    $.post(
      ajaxurl,
      {
        action: "gemini_generate",
        prompt: prompt,
        images: selectedImages,
      },
      function (res) {
        if (res.success) {
          var aiText = res.data.text;
          var aiImages = res.data.images;

          // Gabungkan Gambar di bagian atas sebelum teks (Opsional)
          var imageHtml = "";
          aiImages.forEach(function (url) {
            imageHtml +=
              '<figure class="wp-block-image size-large"><img src="' +
              url +
              '" alt="Tutorial Step" /></figure>';
          });

          var fullHtml = imageHtml + aiText;

          // INSERT KE GUTENBERG (Wordpress Terbaru)
          if (window.wp && wp.blocks) {
            var blocks = wp.blocks.rawHandler({ HTML: fullHtml });
            wp.data.dispatch("core/block-editor").insertBlocks(blocks);
          }
          // INSERT KE CLASSIC EDITOR
          else if (window.tinyMCE && tinyMCE.activeEditor) {
            tinyMCE.activeEditor.execCommand(
              "mceInsertContent",
              false,
              fullHtml,
            );
          }

          $("#gpw_status").html(
            '<span style="color:green">✅ Berhasil dimasukkan ke editor!</span>',
          );
        } else {
          $("#gpw_status").html(
            '<span style="color:red">❌ Error: ' + res.data + "</span>",
          );
        }
        btn.prop("disabled", false).text("Generate");
      },
    );
  });
});
