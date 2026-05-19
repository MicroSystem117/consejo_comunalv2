/**
 * Comunity.js - Funciones compartidas para el sistema del Consejo Comunal
 * 
 * Caracteristicas:
 * - CSRF Protection
 * - Manejo de errores de red
 * - Funciones reutilizables
 * - Mejora de accesibilidad
 */

// Namespace para evitar conflictos
var Comunity = Comunity || {};

// CSRF Token Management
Comunity.CSRF = {
    token: null,
    
    init: function() {
        // Generar token si no existe
        if (!sessionStorage.getItem('csrf_token')) {
            sessionStorage.setItem('csrf_token', this.generateToken());
        }
        this.token = sessionStorage.getItem('csrf_token');
    },
    
    generateToken: function() {
        return Array.from(crypto.getRandomValues(new Uint8Array(32)))
            .map(function(b) { return b.toString(16).padStart(2, '0'); })
            .join('');
    },
    
    getToken: function() {
        if (!this.token) this.init();
        return this.token;
    },
    
    addToFormData: function(formData) {
        formData.append('csrf_token', this.getToken());
    },
    
    validate: function(token) {
        return token === this.getToken();
    }
};

// Utility Functions
Comunity.Utils = {
    escapeHtml: function(s) {
        if (s === null || s === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(s);
        return div.innerHTML;
    },
    
    formatDate: function(dateStr) {
        if (!dateStr) return '-';
        try {
            var date = new Date(dateStr);
            return date.toLocaleDateString('es-VE');
        } catch (e) {
            return dateStr;
        }
    },
    
    showAlert: function(container, message, type) {
        type = type || 'danger';
        container.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            this.escapeHtml(message) +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>' +
            '</div>';
    },
    
    clearAlert: function(container) {
        container.innerHTML = '';
    }
};

// API Functions
Comunity.API = {
    baseUrl: '',
    
    setBaseUrl: function(url) {
        this.baseUrl = url;
    },
    
    fetch: function(endpoint, options) {
        var self = this;
        return new Promise(function(resolve, reject) {
            options = options || {};
            options.headers = options.headers || {};
            
            // Add CSRF token
            if (options.addCSRF !== false) {
                Comunity.CSRF.init();
                options.headers['X-CSRF-Token'] = Comunity.CSRF.getToken();
            }
            
            options.credentials = 'same-origin';
            
            fetch(self.baseUrl + endpoint, options)
                .then(function(response) {
                    var contentType = response.headers.get('content-type');
                    if (contentType && contentType.indexOf('application/json') !== -1) {
                        return response.json();
                    }
                    throw new Error('Respuesta no valida del servidor');
                })
                .then(function(data) { resolve(data); })
                .catch(function(error) {
                    console.error('API Error:', error);
                    reject(error);
                });
        });
    },
    
    get: function(action) {
        return this.fetch('?action=' + action);
    },
    
    post: function(action, data) {
        var self = this;
        return new Promise(function(resolve, reject) {
            var formData = new FormData();
            formData.append('action', action);
            
            if (data) {
                for (var key in data) {
                    if (data.hasOwnProperty(key)) {
                        formData.append(key, data[key]);
                    }
                }
            }
            
            Comunity.CSRF.addToFormData(formData);
            
            self.fetch('', {
                method: 'POST',
                body: formData
            })
            .then(function(data) { resolve(data); })
            .catch(function(error) { reject(error); });
        });
    }
};

// CRUD Operations
Comunity.CRUD = {
    apiUrl: '',
    tableHead: null,
    tableBody: null,
    columns: [],
    
    init: function(apiUrl, columns) {
        this.apiUrl = apiUrl;
        this.columns = columns || [];
        this.tableHead = document.getElementById('tableHead');
        this.tableBody = document.getElementById('tableBody');
    },
    
    renderHead: function() {
        if (!this.tableHead) return;
        
        var html = '<tr>';
        this.columns.forEach(function(col) {
            html += '<th scope="col">' + Comunity.Utils.escapeHtml(col) + '</th>';
        });
        html += '<th scope="col">Acciones</th></tr>';
        this.tableHead.innerHTML = html;
    },
    
    renderBody: function(rows) {
        if (!this.tableBody) return;
        
        this.tableBody.innerHTML = '';
        
        if (!rows || rows.length === 0) {
            this.tableBody.innerHTML = '<tr><td colspan="' + (this.columns.length + 1) + 
                '" class="text-center text-muted">No hay datos disponibles</td></tr>';
            return;
        }
        
        var self = this;
        rows.forEach(function(row) {
            var tr = document.createElement('tr');
            
            self.columns.forEach(function(col) {
                var td = document.createElement('td');
                td.textContent = row[col] || '-';
                tr.appendChild(td);
            });
            
            // Acciones
            var actionsTd = document.createElement('td');
            var id = row[Object.keys(row)[0]];
            
            var editBtn = document.createElement('button');
            editBtn.className = 'btn btn-sm btn-info me-1';
            editBtn.textContent = 'Editar';
            editBtn.setAttribute('data-id', id);
            editBtn.setAttribute('aria-label', 'Editar registro ' + id);
            
            var deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn btn-sm btn-danger';
            deleteBtn.textContent = 'Eliminar';
            deleteBtn.setAttribute('data-id', id);
            deleteBtn.setAttribute('aria-label', 'Eliminar registro ' + id);
            
            actionsTd.appendChild(editBtn);
            actionsTd.appendChild(deleteBtn);
            tr.appendChild(actionsTd);
            
            self.tableBody.appendChild(tr);
        });
    },
    
    load: function() {
        var self = this;
        Comunity.API.get('list')
            .then(function(response) {
                if (response && response.status === 'ok') {
                    self.renderBody(response.data);
                } else {
                    throw new Error(response ? response.message : 'Error desconocido');
                }
            })
            .catch(function(error) {
                console.error('Error cargando datos:', error);
                self.renderBody([]);
            });
    },
    
    save: function(data, id) {
        var action = id ? 'update' : 'create';
        var payload = Object.assign({}, data);
        if (id) payload.id = id;
        
        return Comunity.API.post(action, payload);
    },
    
    delete: function(id) {
        return Comunity.API.post('delete', { id: id });
    }
};

// Modal Management
Comunity.Modal = {
    modalElement: null,
    formElement: null,
    titleElement: null,
    bodyElement: null,
    
    init: function(modalId, formId) {
        this.modalElement = document.getElementById(modalId);
        this.formElement = document.getElementById(formId);
        
        if (this.modalElement) {
            this.titleElement = this.modalElement.querySelector('.modal-title');
            this.bodyElement = this.modalElement.querySelector('.modal-body');
        }
    },
    
    show: function(title) {
        if (this.titleElement) {
            this.titleElement.textContent = title;
        }
        if (this.formElement) {
            this.formElement.reset();
        }
        if (this.modalElement) {
            var modal = new bootstrap.Modal(this.modalElement);
            modal.show();
        }
    },
    
    hide: function() {
        if (this.modalElement) {
            var modal = bootstrap.Modal.getInstance(this.modalElement);
            if (modal) {
                modal.hide();
            }
        }
    },
    
    fill: function(data) {
        if (!this.formElement) return;
        
        for (var key in data) {
            if (data.hasOwnProperty(key)) {
                var field = this.formElement.querySelector('[name="' + key + '"]') ||
                           document.getElementById('e_' + key);
                if (field) {
                    field.value = data[key] || '';
                }
            }
        }
    }
};

// Registro exitoso
// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    Comunity.CSRF.init();
    console.log('Comunity.js inicializado');
});
