<?php
require_once "../src/common.php";
header('Content-Type: text/javascript');
?>
document.addEventListener('DOMContentLoaded', function() {
  let domain = <?=json_encode(_G_public_domain())?>;
  document.querySelectorAll('a[data-email]').forEach(function(emailLink) {
    let user = emailLink.getAttribute('data-email') || <?=json_encode(_G_default_email_user())?>;
    let addr = `${user}@${domain}`;
    if (emailLink.parentElement.classList.contains('inquiry')) {
      let petName = emailLink.closest('tr').querySelector('th.name>*').textContent;
      emailLink.innerHTML = `Email to adopt ${petName}!`;
    }
    emailLink.innerHTML = emailLink.innerHTML || addr;
    emailLink.setAttribute('href', `mailto:${addr}`);
  });
});