# Process Visit Files Command

Este proyecto incluye un comando de consola en Laravel para procesar archivos de visitas almacenados localmente. El comando crea un archivo ZIP con los archivos procesados y luego los elimina del sistema. Esta implementación actual no incluye la conexión a SFTP por motivos demostrativos; en su lugar, se trabaja con una carpeta local de archivos.

## Requisitos previos

- **PHP**: Asegúrate de tener PHP instalado (versión 7.4 o superior).
- **Laravel**: Este proyecto utiliza el framework Laravel, así que asegúrate de tenerlo configurado en tu entorno.
- **MySQL**: Se requiere una base de datos MySQL en el puerto por defecto (3306).

## Configuración

1. **Crear la base de datos:**

    - Crea una base de datos en MySQL con el nombre `visits` utilizando el puerto por defecto (3306).
    - Configura las credenciales de la base de datos en el archivo `.env` de tu proyecto Laravel:

      ```env
      DB_CONNECTION=mysql
      DB_HOST=127.0.0.1
      DB_PORT=3306
      DB_DATABASE=visits
      DB_USERNAME=root
      DB_PASSWORD=
      ```

2. **Levantar la conexión y ejecutar las migraciones:**

    - Ejecuta las migraciones de Laravel para crear las tablas necesarias:

      ```bash
      php artisan migrate
      ```

3. **Ejecutar el comando de procesamiento de archivos:**

    - Una vez que la base de datos esté configurada y las migraciones ejecutadas, puedes correr el comando que procesa los archivos de visitas:

      ```bash
      php artisan visits:process-files
      ```

    - Este comando procesará los archivos en la carpeta local, creará un archivo ZIP con los archivos procesados, y luego eliminará los archivos originales y temporales.

## Detalles importantes

- **Conexión SFTP**: Por motivos demostrativos, la conexión SFTP no está habilitada en esta versión. Actualmente, el comando funciona leyendo archivos desde una carpeta local (`storage/files/`).
- **Eliminación de archivos**: Ten en cuenta que el comando eliminará los archivos procesados después de crear el archivo ZIP. Asegúrate de tener copias de seguridad antes de ejecutarlo en un entorno de producción.

## Notas adicionales

Este proyecto es una demostración de cómo se puede automatizar el procesamiento de archivos utilizando comandos en Laravel

---

¡Gracias por usar este proyecto! 🎉
