# Sistema de Gestión para Consejos Comunales

Sistema web PHP para la gestión integral de datos de consejos comunales, incluyendo gestión de personas, familias, viviendas, calles y usuarios.

## Características

- **Gestión de Personas**: Registro y administración de personas
- **Gestión de Familias**: Control de unidades familiares
- **Gestión de Viviendas**: Catastro de viviendas
- **Gestión de Calles y Cuadras**: Administración de calles y plazas
- **Sistema de Usuarios**: Autenticación y roles de usuario
- **Copias de Seguridad**: Respaldo de base de datos

## Requisitos

- PHP 7.4+
- MySQL/MariaDB
- Servidor web (Apache/Nginx)
- Composer (opcional)

## Estructura del Proyecto

```
consejo_comunalv2/
├── index.php              # Punto de entrada
├── config/                # Configuración
│   ├── constants.php
│   ├── DataBaseManager.php
│   └── security.php
├── src/
│   ├── controllers/       # Controladores
│   ├── models/           # Modelos de datos
│   └── views/            # Vistas
├── public/               # Archivos públicos
│   ├── css/
│   └── js/
├── migrations/           # Migraciones de BD
├── scripts/             # Scripts CLI
└── backups/             # Copias de seguridad
```

## Instalación

1. Clonar el repositorio:
```bash
git clone https://github.com/TU_USUARIO/consejo_comunalv2.git
```

2. Configurar la base de datos en `config/DataBaseManager.php`

3. Ejecutar las migraciones en `migrations/`

4. Acceder al sistema desde el navegador

## Licencia

Este proyecto está bajo la licencia que determine el desarrollador.

## Contribuciones

Las contribuciones son bienvenidas. Por favor, abre un issue o pull request.
