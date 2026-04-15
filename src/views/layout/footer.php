<?php if (!isset($layout) || $layout === 'full'): ?>
            </main>
        </div>
    </div>
    
    <!-- jQuery ya cargado en header -->
    <script src="<?= $base_url ?>/public/vendor/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $base_url ?>/public/js/comunity.js"></script>
    <script src="<?= $base_url ?>/public/js/app.js"></script>
    <script src="<?= $base_url ?>/public/vendor/datatables/jquery.dataTables.min.js"></script>
    
    <!-- User menu scripts -->
    <script>
        // Logout form handler
        document.getElementById('logoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            var logoutUrl = '<?= $base_url ?>/src/controllers/auth.php';
            fetch(logoutUrl, {
                method: 'POST',
                body: formData
            }).then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    window.location.href = '<?= $base_url ?>';
                }
            });
        });
        
        function openPasswordModal() {
            $('#passwordModal').modal('show');
        }
        
        function openSecurityQuestionsModal() {
            $('#securityQuestionsModal').modal('show');
        }
        
        // Toggle Password Visibility
        function togglePassword(inputId, button) {
            var input = document.getElementById(inputId);
            var icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
        
        // Password validation for change password modal
        document.getElementById('new_password').addEventListener('input', function() {
            var pass = this.value;
            var rules = [
                { id: 'ruleLengthCP', valid: pass.length >= 8 },
                { id: 'ruleUpperCP', valid: /[A-Z]/.test(pass) },
                { id: 'ruleLowerCP', valid: /[a-z]/.test(pass) },
                { id: 'ruleDigitCP', valid: /[0-9]/.test(pass) },
                { id: 'ruleSpecialCP', valid: /[^A-Za-z0-9]/.test(pass) }
            ];
            
            rules.forEach(function(r) {
                var el = document.getElementById(r.id);
                if (el) {
                    var icon = el.querySelector('i');
                    if (r.valid) {
                        el.classList.add('text-success');
                        el.classList.remove('text-muted');
                        if (icon) {
                            icon.classList.remove('bi-circle');
                            icon.classList.add('bi-check-circle');
                        }
                    } else {
                        el.classList.remove('text-success');
                        el.classList.add('text-muted');
                        if (icon) {
                            icon.classList.remove('bi-check-circle');
                            icon.classList.add('bi-circle');
                        }
                    }
                }
            });
        });
        
        // Password form handler
        $('#passwordForm').on('submit', function(e) {
            e.preventDefault();
            
            var currentPassword = $('#current_password').val();
            var newPassword = $('#new_password').val();
            var confirmPassword = $('#confirm_password').val();
            
            if (!currentPassword || !newPassword || !confirmPassword) {
                showToast('error', 'Todos los campos son requeridos');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showToast('error', 'Las contraseñas no coinciden');
                return;
            }
            
            // Validate password strength
            var pass = newPassword;
            var errors = [];
            if (pass.length < 8) {
                errors.push('La contraseña debe tener al menos 8 caracteres');
            }
            if (!/[A-Z]/.test(pass)) {
                errors.push('La contraseña debe tener al menos una mayúscula');
            }
            if (!/[a-z]/.test(pass)) {
                errors.push('La contraseña debe tener al menos una minúscula');
            }
            if (!/[0-9]/.test(pass)) {
                errors.push('La contraseña debe tener al menos un número');
            }
            if (!/[^A-Za-z0-9]/.test(pass)) {
                errors.push('La contraseña debe tener al menos un carácter especial');
            }
            
            if (errors.length > 0) {
                showToast('error', errors[0]);
                return;
            }
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize() + '&action=change_password',
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    if (response.status === 'success') {
                        $('#passwordModal').modal('hide');
                        showToast('success', response.message);
                        $('#passwordForm')[0].reset();
                        // Reset validation indicators
                        $('#ruleLengthCP, #ruleUpperCP, #ruleLowerCP, #ruleDigitCP, #ruleSpecialCP').addClass('text-muted').removeClass('text-success');
                        $('#ruleLengthCP i, #ruleUpperCP i, #ruleLowerCP i, #ruleDigitCP i, #ruleSpecialCP i').removeClass('bi-check-circle').addClass('bi-circle');
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function(xhr) {
                    console.log('Error status:', xhr.status);
                    console.log('Error response:', xhr.responseText);
                    var errorMsg = 'Error al procesar la solicitud';
                    if (xhr.responseText) {
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            if (resp.message) errorMsg = resp.message;
                        } catch(e) {}
                    }
                    showToast('error', errorMsg);
                }
            });
        });
        
        // Security questions form handler
        $('#securityQuestionsForm').on('submit', function(e) {
            e.preventDefault();
            
            var sq1 = $('#sq1').val();
            var sa1 = $('#sa1').val().trim();
            var sq2 = $('#sq2').val();
            var sa2 = $('#sa2').val().trim();
            
            if (!sq1 || !sa1 || !sq2 || !sa2) {
                showToast('error', 'Por favor completa todas las preguntas y respuestas');
                return;
            }
            
            if (sq1 === sq2) {
                showToast('error', 'Las preguntas deben ser diferentes');
                return;
            }
            
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: $(this).serialize() + '&action=security_questions',
                dataType: 'json',
                success: function(response) {
                    console.log('Response:', response);
                    if (response.status === 'success' || response.success === true) {
                        $('#securityQuestionsModal').modal('hide');
                        showToast('success', response.message);
                        $('#sq1, #sq2').val('');
                        $('#sa1, #sa2').val('');
                        $('#sqAlert').html('');
                    } else {
                        showToast('error', response.message);
                    }
                },
                error: function(xhr) {
                    console.log('Error status:', xhr.status);
                    console.log('Error response:', xhr.responseText);
                    var errorMsg = 'Error al procesar la solicitud';
                    if (xhr.responseText) {
                        try {
                            var resp = JSON.parse(xhr.responseText);
                            if (resp.message) errorMsg = resp.message;
                        } catch(e) {}
                    }
                    showToast('error', errorMsg);
                }
            });
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
<?php else: ?>
        </div>
    </div>
</div>
    <!-- jQuery ya cargado en header -->
    <script src="<?= $base_url ?>/public/vendor/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= $base_url ?>/public/js/comunity.js"></script>
    <script src="<?= $base_url ?>/public/js/app.js"></script>
<?php endif; ?>
</body>
</html>
