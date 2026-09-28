/**
 * School Fees IS - minimal progressive enhancement (no framework).
 */
(function () {
  'use strict';

  // Sidebar toggle on small screens.
  var toggle = document.getElementById('navToggle');
  var sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });
    document.addEventListener('click', function (e) {
      if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== toggle) {
        sidebar.classList.remove('open');
      }
    });
  }

  // Confirmation dialogs: <form data-confirm="Are you sure?">
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  // Student ID lookup on the payment recording form:
  // <input data-lookup="student"> + <div data-lookup-result>
  var lookupInput = document.querySelector('[data-lookup="student"]');
  var resultBox = document.querySelector('[data-lookup-result]');
  if (lookupInput && resultBox) {
    var timer = null;
    var lastValue = '';
    lookupInput.addEventListener('input', function () {
      var value = lookupInput.value.trim();
      clearTimeout(timer);
      if (value.length < 4 || value === lastValue) { return; }
      timer = setTimeout(function () {
        lastValue = value;
        fetch('/lookup/student?id=' + encodeURIComponent(value), {
          headers: { 'Accept': 'application/json' }
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.found) {
              resultBox.innerHTML = '✅ <strong>' + data.name + '</strong> — ' + data.class_name +
                ' · outstanding: <strong>' + data.outstanding + '</strong>' +
                ' <input type="hidden" name="student_db_id" value="' + data.id + '">';
              resultBox.setAttribute('data-ok', '1');
            } else {
              resultBox.textContent = '❌ No student found for this ID.';
              resultBox.removeAttribute('data-ok');
            }
          })
          .catch(function () {
            resultBox.textContent = 'Lookup failed — you may still type the Student ID and submit.';
            resultBox.removeAttribute('data-ok');
          });
      }, 350);
    });
  }
})();
