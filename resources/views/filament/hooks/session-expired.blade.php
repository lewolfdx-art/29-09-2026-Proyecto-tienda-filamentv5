<script>
    (function () {
        var leaving = false;

        // Al cerrar sesión, el aviso no tiene sentido: la página ya se está yendo.
        document.addEventListener('submit', function (event) {
            var action = (event.target && event.target.action) || '';

            if (action.indexOf('/logout') !== -1) {
                leaving = true;
            }
        }, true);

        window.addEventListener('beforeunload', function () { leaving = true; });
        window.addEventListener('pagehide', function () { leaving = true; });

        function onExpired() {
            // Sesión vencida de verdad: volvemos al ingreso, sin ventana emergente.
            if (!leaving) {
                leaving = true;
                window.location.assign(@json(route('login')));
            }
        }

        function register() {
            if (typeof Livewire.interceptRequest === 'function') {
                // Livewire 4
                Livewire.interceptRequest(function (context) {
                    context.onError(function (error) {
                        if (error.response && error.response.status === 419) {
                            error.preventDefault();
                            onExpired();
                        }
                    });
                });
            } else {
                // Livewire 3
                Livewire.hook('request', function (context) {
                    context.fail(function (failure) {
                        if (failure.status === 419) {
                            failure.preventDefault();
                            onExpired();
                        }
                    });
                });
            }
        }

        if (window.Livewire) {
            register();
        } else {
            document.addEventListener('livewire:init', register);
        }
    })();
</script>