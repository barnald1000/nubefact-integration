# AGENTS.md

## Alcance

- La última orden explícita del usuario define el alcance.
- No incorporar integraciones, refactors, dependencias ni cambios de esquema no solicitados.
- Diferenciar con claridad entre comportamiento verificado, propuesta y decisión pendiente.

## PHP: lint y pruebas

Aplica cuando se cree, modifique o elimine código PHP.

1. Ejecutar `composer lint:fix`.
2. Ejecutar `composer lint` y corregir cualquier fallo de los archivos modificados.
3. Escribir y ejecutar las pruebas del comportamiento nuevo o cambiado. Ejecutar además las pruebas relacionadas.
4. Si el cambio solo modifica formato, comentarios o documentación, no ejecutar PHPUnit; indicarlo en el reporte.
5. No usar HTTP real, `dd()` ni estado externo en pruebas.

Comandos:

```bash
composer lint
composer lint:diff
composer lint:fix
vendor/bin/phpunit
```

## Convenciones mínimas

- Todo archivo PHP usa `declare(strict_types=1)`.
- Usar tipos y visibilidad explícitos.
- No dejar imports sin uso.
- Los nombres deben describir el hecho o concepto del dominio.
- Los comentarios solo existen si explican información que no es visible en el código inmediato.

## Reporte

Al cerrar una tarea, indicar de forma concreta qué se cambió y qué validaciones se ejecutaron. Si algo no se pudo ejecutar, informar el bloqueo exacto.
