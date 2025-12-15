jQuery(function ($) {

  // Accepts:
  // 05XXXXXXXX (mobile)
  // 0[1-4,6-7]XXXXXXX (landline)
  // +9665XXXXXXXX / +966[1-4,6-7]XXXXXXX
  // 009665XXXXXXXX / 00966[1-4,6-7]XXXXXXX
  const saudiPhoneRegex = /^(05\d{8}|0[1-4,6-7]\d{7}|\+9665\d{8}|\+966[1-4,6-7]\d{7}|009665\d{8}|00966[1-4,6-7]\d{7})$/;

  const $phone = $('input[name="phone"]');
  if (!$phone.length) return;

  const $msg = $('<div class="pl-phone-msg" style="margin-top:6px;font-size:12px;display:none;"></div>');
  $phone.after($msg);

  function normalize(v) {
    return (v || '').replace(/[\s\-\(\)]/g, '');
  }

  function validateNow() {
    const val = normalize($phone.val());

    // empty = no error (your PHP validation can still require it if needed)
    if (!val.length) {
      $msg.hide().text('');
      $phone.removeClass('pl-phone-invalid pl-phone-valid');
      return true;
    }

    const ok = saudiPhoneRegex.test(val);

    if (!ok) {
      $msg.text('Please enter a valid Saudi phone number (e.g., 0501234567 or +966501234567)')
          .css('color', 'red')
          .show();
      $phone.addClass('pl-phone-invalid').removeClass('pl-phone-valid');
      return false;
    } else {
      $msg.text('Valid phone number')
          .css('color', 'green')
          .show();
      $phone.addClass('pl-phone-valid').removeClass('pl-phone-invalid');
      return true;
    }
  }

  // Live validation
  $phone.on('input blur', validateNow);

  // Block submit if invalid (register + login forms)
  $('form.register, form.woocommerce-form-login').on('submit', function (e) {
    if (!validateNow()) {
      e.preventDefault();
      $phone.trigger('focus');
    }
  });

});
