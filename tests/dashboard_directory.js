// Run with: node tests/dashboard_directory.js
// Exercise the directory's rendered script without browser dependencies.
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const vm = require('node:vm');
const path = require('node:path');
const fixture = JSON.parse(execFileSync('php', [path.join(__dirname, 'family_views.php'), '--directory-fixture'], { encoding: 'utf8' }));
function element(attributes = {}) {
    const classes = new Set();
    const handlers = {};
    return {
        value: '', textContent: '', hidden: false, focused: false,
        getAttribute: key => attributes[key],
        setAttribute: (key, value) => { attributes[key] = value; },
        classList: {
            contains: name => classes.has(name),
            toggle: (name, on) => on ? classes.add(name) : classes.delete(name)
        },
        addEventListener: (name, callback) => { handlers[name] = callback; },
        trigger: name => handlers[name](),
        focus() { this.focused = true; }
    };
}
const cards = fixture.cards.map(card => {
    const result = element({ 'data-name': card.name, 'data-clan': card.clan });
    result.classList.toggle('hidden', card.hidden);
    return result;
});
const tabs = ['all', '1', '2'].map(clan => element({ 'data-clan': clan }));
const elements = Object.fromEntries(['search', 'count', 'no-results', 'show-more-members', 'directory-page-status'].map(id => [id, element()]));
const document = {
    getElementById: id => elements[id],
    querySelectorAll: selector => selector === '.member-card' ? cards : tabs
};
vm.runInNewContext('(function () {' + fixture.script + '})();', { document });
const shown = () => cards.filter(card => !card.classList.contains('hidden'));
const search = value => { elements.search.value = value; elements.search.trigger('input'); };

assert.equal(shown().length, 12);
elements['show-more-members'].trigger('click');
assert.equal(shown().length, 24);
assert.equal(cards[12].focused, true, 'Keyboard focus should move to newly revealed relatives.');
elements['show-more-members'].trigger('click');
assert.equal(shown().length, 25);
assert.equal(elements['show-more-members'].hidden, true);

search('relative 25');
assert.equal(shown().length, 1, 'Search must find a relative beyond the first page.');
assert.equal(shown()[0].getAttribute('data-clan'), '2');
assert.equal(elements['directory-page-status'].textContent, 'Showing 1 of 1 relatives');
search('no-such-relative');
assert.equal(shown().length, 0);
assert.equal(elements['no-results'].classList.contains('hidden'), false);
search('');
assert.equal(shown().length, 12, 'Changing the search should reset the page size.');
tabs[2].trigger('click');
assert.equal(shown().length, 10);
assert.ok(shown().every(card => card.getAttribute('data-clan') === '2'));
assert.equal(tabs[2].getAttribute('aria-pressed'), 'true');
assert.equal(tabs[0].getAttribute('aria-pressed'), 'false');
assert.equal(elements['show-more-members'].hidden, true);
search('relative 1');
assert.equal(shown().length, 5, 'Clan and name filters must work together.');
tabs[0].trigger('click');
assert.equal(shown().length, 12);
console.log('Directory pagination, search, clan filters, focus, and empty results passed.');
