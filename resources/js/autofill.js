// Form autofill — dev convenience for prefilling empty form fields with
// faker-style data. Activated when a `<meta name="form-autofill" content="1">`
// tag is present (rendered by the admin Settings toggle).
//
// Skipped fields: password, hidden, file, submit, button, readonly, disabled,
// CSRF / method-spoof, captcha, anything with `data-no-autofill`.

const META_NAME = 'form-autofill';

function isEnabled() {
    const meta = document.querySelector(`meta[name="${META_NAME}"]`);
    return meta?.getAttribute('content') === '1';
}

const FIRST_NAMES = ['Asha', 'Ravi', 'Priya', 'Arjun', 'Meera', 'Karan', 'Neha', 'Vikram', 'Sara', 'Dev'];
const LAST_NAMES = ['Patel', 'Sharma', 'Kumar', 'Singh', 'Iyer', 'Reddy', 'Khan', 'Joshi', 'Mehta', 'Bose'];
const CITIES = ['Ratnagiri', 'Mumbai', 'Chennai', 'Bengaluru', 'Pune', 'Hyderabad', 'Jaipur', 'Kolkata'];
const REGIONS = ['Konkan, Maharashtra', 'Tamil Nadu', 'Kerala', 'Karnataka', 'Andhra Pradesh', 'Gujarat'];
const COMPANY_PREFIXES = ['Sunrise', 'Mango', 'Orchard', 'Valley', 'Konkan', 'Golden', 'Saffron', 'Coastal'];
const COMPANY_SUFFIXES = ['Orchards', 'Farms', 'Grove', 'Estate', 'Co-op', 'Gardens'];
const ADJECTIVES = ['ripe', 'sweet', 'golden', 'fragrant', 'sun-kissed', 'lush', 'tangy'];
const NOUNS = ['mango', 'orchard', 'harvest', 'season', 'farm', 'grove', 'fruit'];
const SENTENCES = [
    'Family-run grove harvesting Alphonso since the seventies.',
    'Hand-picked, naturally ripened, ships within two days of plucking.',
    'A small but loved orchard producing seasonal favourites.',
    'Premium grade fruit, packed in straw and cardboard.',
    'Limited stock each week — order ahead during peak season.',
];

function pick(list) {
    return list[Math.floor(Math.random() * list.length)];
}

function randomFirstName() { return pick(FIRST_NAMES); }
function randomLastName() { return pick(LAST_NAMES); }
function randomFullName() { return `${randomFirstName()} ${randomLastName()}`; }
function randomCity() { return pick(CITIES); }
function randomRegion() { return pick(REGIONS); }
function randomLocation() { return `${randomCity()}, ${randomRegion()}`; }
function randomCompany() { return `${pick(COMPANY_PREFIXES)} ${pick(COMPANY_SUFFIXES)}`; }
function randomWords(count = 2) {
    return Array.from({ length: count }, () => pick(ADJECTIVES)).join(' ');
}
function randomTitle() {
    return `${pick(ADJECTIVES)} ${pick(NOUNS)}`.replace(/\b\w/g, c => c.toUpperCase());
}
function randomSentence() { return pick(SENTENCES); }
function randomParagraph(count = 2) {
    return Array.from({ length: count }, () => randomSentence()).join(' ');
}
function randomEmail() {
    const handle = `${randomFirstName()}.${randomLastName()}${Math.floor(Math.random() * 90 + 10)}`.toLowerCase();
    return `${handle}@example.com`;
}
function randomPhone() {
    const seg = () => Math.floor(Math.random() * 9000 + 1000);
    return `+91 ${seg()} ${seg()}`;
}
function randomUrl() { return `https://${pick(COMPANY_PREFIXES).toLowerCase()}.example.com`; }
function randomDate() {
    const d = new Date();
    d.setDate(d.getDate() - Math.floor(Math.random() * 365));
    return d.toISOString().slice(0, 10);
}
function randomNumber(field) {
    const min = Number(field.min) || 1;
    const max = Number(field.max) || (field.step && Number(field.step) < 1 ? 999.99 : 999);
    const step = Number(field.step) || 1;
    const range = max - min;
    const raw = min + Math.random() * range;
    if (step < 1) {
        return raw.toFixed(2);
    }
    return String(Math.floor(raw));
}

function shouldSkip(field) {
    if (!field.name && !field.id) return true;
    const skipTypes = ['password', 'hidden', 'file', 'submit', 'button', 'image', 'reset'];
    if (skipTypes.includes(field.type)) return true;
    if (field.hasAttribute('readonly')) return true;
    if (field.hasAttribute('disabled')) return true;
    if ('noAutofill' in field.dataset) return true;
    if (field.name === '_token' || field.name === '_method') return true;
    const lowerName = (field.name || '').toLowerCase();
    if (lowerName.includes('captcha')) return true;
    if (lowerName.includes('password')) return true;
    return false;
}

function isEmpty(field) {
    if (field.tagName === 'SELECT') {
        return field.value === '' || field.value == null;
    }
    return (field.value ?? '').trim() === '';
}

function generateFor(field) {
    const tag = field.tagName;
    const type = field.type;
    const name = (field.name || '').toLowerCase();
    const placeholder = (field.placeholder || '').toLowerCase();
    const id = (field.id || '').toLowerCase();
    const blob = `${name} ${placeholder} ${id}`;

    if (tag === 'SELECT') {
        // Only autofill *required* selects. Optional selects are typically
        // filter dropdowns (with an empty "All" option) or genuinely optional
        // foreign-key pickers — overwriting either silently changes the page
        // (filter forms auto-submit on change) or stomps on the user's
        // deliberate "no choice" selection.
        if (!field.required) return null;
        const opts = Array.from(field.options).filter(o => o.value !== '' && !o.disabled);
        if (opts.length === 0) return null;
        return pick(opts).value;
    }

    if (type === 'email' || blob.includes('email')) return randomEmail();
    if (type === 'tel' || blob.includes('phone') || blob.includes('tel')) return randomPhone();
    if (type === 'url' || blob.includes('url') || blob.includes('website')) return randomUrl();
    if (type === 'date') return randomDate();
    if (type === 'number') return randomNumber(field);

    if (blob.includes('first_name') || blob.includes('firstname')) return randomFirstName();
    if (blob.includes('last_name') || blob.includes('lastname') || blob.includes('surname')) return randomLastName();

    if (blob.includes('farm') || blob.includes('company') || blob.includes('orchard') || blob.includes('estate')) {
        return randomCompany();
    }

    if (name === 'name' || blob.endsWith(' name') || blob.includes('full_name') || blob.includes('username')) {
        return randomFullName();
    }

    if (blob.includes('region') || blob.includes('location') || blob.includes('city') ||
        blob.includes('address') || blob.includes('country')) {
        return randomLocation();
    }

    if (blob.includes('description') || blob.includes('message') || blob.includes('note') ||
        blob.includes('bio') || blob.includes('comment') || blob.includes('reason')) {
        return randomParagraph();
    }

    if (tag === 'TEXTAREA') return randomParagraph();
    if (blob.includes('title') || blob.includes('subject')) return randomTitle();
    if (blob.includes('tag') || blob.includes('keyword')) return randomWords(1);
    if (blob.includes('slug')) return `${pick(COMPANY_PREFIXES).toLowerCase()}-${pick(NOUNS)}-${Math.floor(Math.random() * 999)}`;

    return randomWords(2);
}

function pulse(field) {
    const prev = field.style.transition;
    field.style.transition = 'background-color 0.4s ease-out';
    field.style.backgroundColor = '#fef3c7';
    setTimeout(() => {
        field.style.backgroundColor = '';
        setTimeout(() => { field.style.transition = prev; }, 400);
    }, 50);
}

function autofillForm(form) {
    let count = 0;
    form.querySelectorAll('input, textarea, select').forEach(field => {
        if (shouldSkip(field)) return;
        if (!isEmpty(field)) return;
        const value = generateFor(field);
        if (value === null) return;
        field.value = value;
        field.dispatchEvent(new Event('input', { bubbles: true }));
        field.dispatchEvent(new Event('change', { bubbles: true }));
        pulse(field);
        count++;
    });

    // Required radio groups with nothing selected get the first option checked.
    const radioGroups = {};
    form.querySelectorAll('input[type="radio"]').forEach(r => {
        if (!r.name || shouldSkip(r)) return;
        (radioGroups[r.name] = radioGroups[r.name] || []).push(r);
    });
    Object.values(radioGroups).forEach(radios => {
        if (radios.some(r => r.checked)) return;
        if (!radios.some(r => r.required)) return;
        radios[0].checked = true;
        radios[0].dispatchEvent(new Event('change', { bubbles: true }));
        pulse(radios[0]);
        count++;
    });

    return count;
}

function run() {
    try {
        if (!isEnabled()) return;
        let total = 0;
        document.querySelectorAll('form').forEach(form => {
            total += autofillForm(form);
        });
        if (total > 0) {
            console.info(`[autofill] prefilled ${total} field${total === 1 ? '' : 's'}`);
        }
    } finally {
        // Deterministic signal for browser tests — "we've had our shot at
        // filling the page", whether or not autofill was enabled. Tests can
        // poll `window.__autofill.done` instead of racing `value()` queries
        // against DOMContentLoaded.
        window.__autofill.done = true;
    }
}

window.__autofill = { run, autofillForm, isEnabled, done: false };

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
} else {
    run();
}
