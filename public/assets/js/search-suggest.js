(function () {
    var input = document.querySelector('[data-search-input]');
    var box = document.querySelector('[data-search-suggest]');
    if (!input || !box) {
        return;
    }
    var timer = null;
    var render = function (results) {
        box.innerHTML = '';
        if (!results.length) {
            box.hidden = true;
            return;
        }
        results.forEach(function (item) {
            var link = document.createElement('a');
            link.href = item.url;
            link.innerHTML = '<strong></strong><span></span>';
            link.querySelector('strong').textContent = item.name;
            link.querySelector('span').textContent = item.category;
            box.appendChild(link);
        });
        box.hidden = false;
    };
    input.addEventListener('input', function () {
        var term = input.value.trim();
        clearTimeout(timer);
        if (term.length < 2) {
            box.hidden = true;
            return;
        }
        timer = setTimeout(function () {
            fetch('/search/suggest?q=' + encodeURIComponent(term))
                .then(function (response) { return response.ok ? response.json() : []; })
                .then(render)
                .catch(function () { box.hidden = true; });
        }, 220);
    });
    document.addEventListener('click', function (event) {
        if (!box.contains(event.target) && event.target !== input) {
            box.hidden = true;
        }
    });
})();
