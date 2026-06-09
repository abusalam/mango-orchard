{{-- Inline theme bootstrap.
     Reads the `theme_preference` cookie (auto|light|dark) and sets the
     `dark` class on <html> BEFORE the body renders, so dark-mode users
     never see a flash of light styles. `auto` falls back to the OS via
     `prefers-color-scheme`. Lives in <head> of every layout. --}}
<script>
    (function () {
        try {
            var pref = (document.cookie.split('; ').find(function (c) {
                return c.indexOf('theme_preference=') === 0;
            }) || '').split('=')[1] || 'auto';

            var systemDark = window.matchMedia
                && window.matchMedia('(prefers-color-scheme: dark)').matches;

            var useDark = pref === 'dark' || (pref !== 'light' && systemDark);

            document.documentElement.classList.toggle('dark', useDark);
            document.documentElement.dataset.themePreference = pref;
        } catch (e) {
            // Cookie parsing or matchMedia errors fall through to light.
        }
    })();
</script>
