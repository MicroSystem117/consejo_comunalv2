# Subir el proyecto a GitHub

## 1. Crear el repositorio en GitHub

1. Ve a: https://github.com/new
2. Repository name: `consejo_comunalv2`
3. Descripción: "Sistema de gestión para consejos comunales - PHP"
4. Visibility: Public o Private (tú decides)
5. **Importante:** NO marques "Add a README file"
6. Click en "Create repository"

## 2. Conectar y subir el código

Reemplaza `TU_USUARIO` con tu nombre de usuario de GitHub y ejecuta:

```bash
cd /opt/lampp/htdocs/consejo_comunalv2.0.0

# Agregar remote origin (reemplaza TU_USUARIO)
git remote add origin https://github.com/TU_USUARIO/consejo_comunalv2.git

# Renombrar rama a main
git branch -M main

# Subir al repositorio
git push -u origin main
```

## 3. Autenticación

Cuando ejecutes `git push`, Git te solicitará autenticación:

- **Si usas HTTPS:** Ingresa tu usuario y contraseña (o token personal)
  - Para crear un token: GitHub Settings → Developer settings → Personal access tokens → Generate new token
  - El token se usa como contraseña

- **Si usas SSH:** Necesitas configurar SSH keys en tu cuenta de GitHub

## Configuración de git (si no lo has hecho)

```bash
git config user.name "Tu Nombre"
git config user.email "tu@email.com"
```

## Verificar estado

```bash
git status
git log --oneline
```
