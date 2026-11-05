<script>
    (() => {
        try {
            const theme = localStorage.getItem('stayvia-theme') || localStorage.getItem('stayvia-navbar-theme');
            document.documentElement.classList.toggle(
                'dark',
                theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches),
            );
        } catch (error) {
            document.documentElement.classList.toggle(
                'dark',
                window.matchMedia('(prefers-color-scheme: dark)').matches,
            );
        }
    })();
</script>
