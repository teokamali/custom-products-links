jQuery(document).ready(function ($) {
  $(".teos-product-dropdown").select2({
    ajax: {
      url: teosAdmin.ajax_url,
      dataType: "json",
      delay: 250,
      data: function (params) {
        return {
          action: "teos_fetch_products",
          q: params.term, // search term
        };
      },
      processResults: function (data) {
        return {
          results: data.map(function (item) {
            return {
              id: item.id,
              text: item.text,
              image: item.image,
            };
          }),
        };
      },
      cache: true,
    },
    templateResult: formatProduct,
    templateSelection: formatProductSelection,
    minimumInputLength: 2,
  });

  function formatProduct(product) {
    if (!product.id) {
      return product.text;
    }
    var image = product.image
      ? '<img src="' +
        product.image +
        '" style="width:50px; height:50px; margin-right:10px;" />'
      : "";
    return $("<span>" + image + product.text + "</span>");
  }

  function formatProductSelection(product) {
    return product.text || product.id;
  }
});
jQuery(document).ready(function ($) {
  $(".copy-shortlink").on("click", function () {
    var shortlink = $(this).data("shortlink");
    var $tempInput = $("<input>");
    $("body").append($tempInput);
    $tempInput.val(shortlink).select();
    document.execCommand("copy");
    $tempInput.remove();
    var $button = $(this);
    var originalText = $button.text();
    $button.text("Copied!");
    setTimeout(function () {
      $button.text(originalText);
    }, 3000);
  });

  $("#refresh-shortlinks").on("click", function () {
    $.ajax({
      url: teosAdmin.ajax_url,
      method: "POST",
      data: {
        action: "teos_refresh_shortlinks",
        _ajax_nonce: teosAdmin.nonce,
      },
      success: function (response) {
        if (response.success) {
          alert(response.data);
          location.reload();
        } else {
          alert(response.data);
        }
      },
      error: function () {
        alert(teosAdmin.refresh_error);
      },
    });
  });
});
