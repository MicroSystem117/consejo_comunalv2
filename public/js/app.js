/**
 * app.js - JavaScript principal del Sistema del Consejo Comunal
 */

// Toast notification function
window.showToast = function(type, message) {
    var toastId = 'toast-' + Date.now();
    var bgClass = type === 'success' ? 'bg-success' : (type === 'error' ? 'bg-danger' : (type === 'warning' ? 'bg-warning' : 'bg-info'));
    
    var toastHTML = 
        '<div id="' + toastId + '" class="toast ' + bgClass + ' text-white" role="alert" aria-live="assertive" aria-atomic="true" style="position:fixed;top:20px;right:20px;z-index:99999;min-width:300px;">' +
        '  <div class="toast-header ' + bgClass + ' text-white">' +
        '    <strong class="me-auto">' + (type === 'success' ? 'Éxito' : (type === 'error' ? 'Error' : (type === 'warning' ? 'Advertencia' : 'Información'))) + '</strong>' +
        '    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>' +
        '  </div>' +
        '  <div class="toast-body">' + message + '</div>' +
        '</div>';
    
    $('body').append(toastHTML);
    
    var toastEl = document.getElementById(toastId);
    var toast = new bootstrap.Toast(toastEl, { delay: 5000 });
    toast.show();
    
    // Remove after hide
    $(toastEl).on('hidden.bs.toast', function() {
        $(this).remove();
    });
};

$(document).ready(function() {
    var baseUrl = window.baseUrl || $('body').data('base-url') || '';
    
    // Crear modal genérico
    function createModal() {
        var modalHTML = 
            '<div id="entityModal" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:99999;">' +
                '<div style="background:white;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.2);width:90%;max-width:500px;padding:20px;">' +
                    '<h3 id="modalTitle" style="margin:0 0 20px 0;font-size:1.25rem;">Nueva Entidad</h3>' +
                    '<div id="modalBody"></div>' +
                    '<div style="margin-top:20px;display:flex;justify-content:flex-end;gap:10px;">' +
                        '<button onclick="closeModal()" style="padding:10px 20px;border-radius:8px;border:1px solid #6c757d;background:white;cursor:pointer;">Cancelar</button>' +
                        '<button onclick="saveEntity()" style="padding:10px 20px;border-radius:8px;border:none;background:#0d6efd;color:white;cursor:pointer;">Guardar</button>' +
                    '</div>' +
                '</div>' +
            '</div>';
        $('body').append(modalHTML);
    }
    
    if (!$('#entityModal').length) {
        createModal();
    }
    
    // Abrir modal
    window.openModal = function(entityType, id) {
        window.currentEntity = entityType;
        window.editId = id || null;
        
        var titles = {
            'persona': id ? 'Editar Persona' : 'Nueva Persona',
            'familia': id ? 'Editar Familia' : 'Nueva Familia',
            'vivienda': id ? 'Editar Vivienda' : 'Nueva Vivienda',
            'calle': id ? 'Editar Calle' : 'Nueva Calle',
            'plaza': id ? 'Editar Plaza' : 'Nueva Plaza'
        };
        
        $('#modalTitle').text(titles[entityType] || 'Entidad');
        $('#modalBody').html(generateForm(entityType, id));
        $('#entityModal').css('display', 'flex');
    };
    
    // Cerrar modal
    window.closeModal = function() {
        $('#entityModal').css('display', 'none');
        window.currentEntity = null;
        window.editId = null;
    };
    
    // Generar formulario según tipo
    function generateForm(entityType, id) {
        var html = '<input type="hidden" name="id" value="' + (id || '') + '">';
        
        if (entityType === 'persona') {
            html += '<div class="form-group"><label class="form-label">Nombre *</label>';
            html += '<input type="text" class="form-control" name="name" id="inputName" required></div>';
            html += '<div class="form-group"><label class="form-label">Cedula *</label>';
            html += '<input type="number" class="form-control" name="ci" id="inputCi" required></div>';
            html += '<div class="form-group"><label class="form-label">Fecha de Nacimiento</label>';
            html += '<input type="date" class="form-control" name="birth" id="inputBirth"></div>';
        } else if (entityType === 'familia') {
            html += '<div class="form-group"><label class="form-label">Apellido Familiar *</label>';
            html += '<input type="text" class="form-control" name="surname" id="inputSurname" required></div>';
        } else if (entityType === 'vivienda') {
            html += '<div class="form-group"><label class="form-label">Numero *</label>';
            html += '<input type="text" class="form-control" name="number" id="inputNumber" required></div>';
        } else if (entityType === 'calle') {
            html += '<div class="form-group"><label class="form-label">Nombre de la Calle *</label>';
            html += '<input type="text" class="form-control" name="name" id="inputName" required></div>';
        } else if (entityType === 'plaza') {
            html += '<div class="form-group"><label class="form-label">Codigo *</label>';
            html += '<input type="text" class="form-control" name="codigo" id="inputCodigo" required></div>';
        }
        
        return html;
    }
    
    // Guardar entidad
    window.saveEntity = function() {
        var entityType = window.currentEntity;
        var id = window.editId;
        var action = id ? 'update' : 'create';
        var endpoint = getEndpoint(entityType);
        
        var data = {};
        
        if (entityType === 'persona') {
            data.name = $('#inputName').val();
            data.ci = $('#inputCi').val();
            data.birth = $('#inputBirth').val();
        } else if (entityType === 'familia') {
            data.surname = $('#inputSurname').val();
        } else if (entityType === 'vivienda') {
            data.number = $('#inputNumber').val();
        } else if (entityType === 'calle') {
            data.name = $('#inputName').val();
        } else if (entityType === 'plaza') {
            data.codigo = $('#inputCodigo').val();
        }
        
        if (id) data.id = id;
        
        $.post(baseUrl + endpoint + '?action=' + action, data, function(response) {
            closeModal();
            location.reload();
        }).fail(function() {
            alert('Error al guardar');
        });
    };
    
    // Obtener endpoint según entidad
    function getEndpoint(entityType) {
        var endpoints = {
            'persona': '/src/controllers/comunity_person.php',
            'familia': '/src/controllers/comunity_family.php',
            'vivienda': '/src/controllers/comunity_house.php',
            'calle': '/src/controllers/comunity_street.php',
            'plaza': '/src/controllers/comunity_square.php'
        };
        return endpoints[entityType] || '/src/controllers/comunity_person.php';
    }
    
    // Editar persona
    window.editPerson = function(id) {
        var personas = window.personas || [];
        var persona = personas.find(function(p) { return p.id_person == id; });
        if (persona) {
            openModal('persona', id);
            setTimeout(function() {
                $('#inputName').val(persona.name_person);
                $('#inputCi').val(persona.ci_person);
                $('#inputBirth').val(persona.birth_person || '');
            }, 100);
        }
    };
    
    // Eliminar persona
    window.deletePerson = function(id) {
        if (!confirm('Esta seguro de eliminar esta persona?')) return;
        
        $.post(baseUrl + '/src/controllers/comunity_person.php?action=delete', { id: id }, function() {
            location.reload();
        }).fail(function() {
            alert('Error al eliminar');
        });
    };
});
