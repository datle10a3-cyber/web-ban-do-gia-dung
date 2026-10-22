$(document).ready(function () {
  // Debounce utility để tránh AJAX spam
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Autocomplete search với debounce
  const debouncedSearch = debounce(function (requestTerm) {
    $.ajax({
      url: "products/search.php",
      dataType: "json",
      data: { term: requestTerm },
      success: function (data) {
        $("#searchInput").autocomplete("option", "source", data);
      },
    });
  }, 300); // Debounce 300ms

  $("#searchInput").autocomplete({
    source: function (request, response) {
      debouncedSearch(request.term);
      // Fallback empty nếu debounce
      response([]);
    },
    minLength: 2,
    select: function (event, ui) {
      window.location.href = "products/detail.php?id=" + ui.item.id;
    },
  });

  // Quick view - Giữ nguyên, AJAX đơn giản
  $(".quick-view").click(function () {
    var productId = $(this).data("id");
    $.ajax({
      url: "products/quick_view.php",
      data: { id: productId },
      success: function (data) {
        $("#quickViewContent").html(data);
        $("#quickViewModal").modal("show");
      },
    });
  });

  // Add to cart notification - Sử dụng show/hide thay fade để nhanh hơn
  function showNotification(message) {
    const $popup = $(".notification-popup");
    $popup
      .text(message)
      .show()
      .delay(3000)
      .queue(function () {
        $(this).hide().dequeue();
      });
  }

  // Countdown flash sale - Tối ưu: Update text thay html, tránh parse
  function updateCountdown(endTime, $countdown) {
    const now = new Date().getTime();
    const distance = endTime - now;
    if (distance > 0) {
      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor(
        (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
      );
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      // Update text nodes riêng để tránh full html rebuild
      $countdown.find(".days").text(days);
      $countdown.find(".hours").text(hours);
      $countdown.find(".minutes").text(minutes);
      $countdown.find(".seconds").text(seconds);

      setTimeout(() => updateCountdown(endTime, $countdown), 1000);
    } else {
      $countdown.html("<div>Hết hạn!</div>");
    }
  }

  // Init countdown - Giả sử HTML có cấu trúc <div class="countdown"><div class="days">0<br>Ngày</div>...</div>
  $(".flash-sale").each(function () {
    const endTime = new Date($(this).data("end")).getTime();
    const $countdown = $(this).find(".countdown"); // Assume inside flash-sale
    updateCountdown(endTime, $countdown);
  });

  // Update cart count - Giữ nguyên, gọi khi cần
  function updateCartCount() {
    $.get("cart/count.php", function (count) {
      $('[href="cart/index.php"]').html(`Giỏ hàng (${count})`);
    });
  }

  // AJAX add to cart - Giữ nguyên
  $(".add-to-cart").click(function (e) {
    e.preventDefault();
    const productId = $(this).data("id");
    const quantity = $("#quantity").val() || 1;
    $.post(
      "cart/add.php",
      {
        product_id: productId,
        quantity: quantity,
        csrf: $("input[name=csrf]").val(),
      },
      function (data) {
        if (data.success) {
          showNotification("Thêm vào giỏ thành công!");
          updateCartCount();
        }
      },
      "json"
    );
  });

  // Wishlist toggle - Giữ nguyên
  $(".wishlist-btn").click(function () {
    const productId = $(this).data("id");
    $.post(
      "user/wishlist.php",
      {
        product_id: productId,
        action: "toggle",
        csrf: $("input[name=csrf]").val(),
      },
      function (data) {
        if (data.success) {
          $(this).toggleClass("text-danger").find("i").toggleClass("far fas");
        }
      }
    );
  });

  // Init cart count on load
  updateCartCount();
});
