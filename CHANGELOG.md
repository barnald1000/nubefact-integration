# Registro de cambios

Todos los cambios relevantes de esta librería se documentan aquí. El formato sigue [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y el versionado sigue [Semantic Versioning](https://semver.org/lang/es/). Las versiones anteriores a la 0.5.0 no tienen registro en este archivo.

## [0.5.1] - 2026-10-11

### Añadido

- `IgvRate::of()`, para que la aplicación defina la tasa de IGV del documento a partir de un porcentaje (`int` o `float`) mayor que 0, como máximo 100 y con hasta 2 decimales. `IgvRate::standard()` sigue siendo la tasa general de 18 %.
- `InvalidIgvRate`, que se lanza cuando la tasa no cumple esas condiciones.

## [0.5.0] - 2026-10-11

La librería deja de recibir importes calculados y pasa a calcularlos ella misma a partir de los hechos comerciales del documento. Los ítems y la cabecera que se envían a Nubefact salen siempre del mismo cálculo.

### Cambios que rompen compatibilidad

- `FinancialTransaction` ahora entrega `igvRate(): IgvRate` y `lines(): Lines`, y ya no extiende `Totalizable`. Deja de pedir `taxPercentage()`, `items()`, `total()`, `netTotal()`, `totalDiscounts()` y `totalTaxes()`.
- Las implementaciones deben implementar `FinancialTransaction` directamente: `TransactionEntry` se eliminó.
- Las líneas se entregan como `Line` (hechos), no como implementaciones de `TransactionEntryItem`.

El uso de la nueva API está descrito en el [`readme.md`](readme.md).

### Añadido

- `Line::onerous()` para describir una línea con sus hechos: ítem, cantidad, precio unitario, tratamiento de IGV y descuento opcional de toda la línea.
- `Amount` (importe de hasta 2 decimales, no negativo) y `Quantity` (entero mayor que cero).
- `IGVTreatment` con cuatro tratamientos: gravada con IGV incluido, gravada sin IGV, exonerada e inafecta. Incluye `key()` y `fromKey()` para persistirlo.
- `Lines`, `LineProjections`: colecciones tipadas.
- `LineProjection` y `DocumentProjection`: calculan los importes de cada línea y los totales de cabecera.
- `DecimalFormat`: único punto que decide cómo se escribe un decimal en el payload.
- La cabecera envía `total_descuento`, `total_exonerada` y `total_inafecta`.
- Los ítems envían `codigo_producto_sunat` cuando el ítem lo tiene.

### Cambiado

- `valor_unitario` (sin IGV, hasta 10 decimales) y `precio_unitario` (con IGV) se envían como el precio antes del descuento; `descuento` se envía antes de impuestos, como define Nubefact.
- `tipo_de_igv` refleja el tratamiento de cada línea (1, 8 o 9) en lugar de un valor fijo.
- Cada importe se calcula exacto y se redondea una sola vez (half-up). Los totales de cabecera son la suma de las líneas ya redondeadas. Con IGV incluido, el total es exactamente el importe cobrado.
- `HeaderSchema` y `LineSchema` solo traducen importes ya calculados; `Schema` se limita a validar las claves requeridas.
- `Totalizable` expone `total()`, `totalDiscounts()` y `totalTaxes()` como `BigDecimal` y lo implementan las proyecciones.
- `cantidad` se envía como entero.
- La librería valida solo datos monetarios; el resto (cliente, ítem, unidad de medida) lo valida Nubefact.
- `brick/math` admite `^0.14.4 || ^1.0`, para convivir con proyectos que usan la serie 0.14.

### Eliminado

- `TransactionEntry`, `TransactionEntryItem`, `Items`, `TaxEvaluator`, `FinancialCalculator` y `TransactionSummarizer`.
- `Totalizable::netTotal()`.
- La dependencia `brick/money`, que ya no se usa.

### Corregido

- Los importes de los ítems (`valor_unitario`, `precio_unitario`, `descuento`, `subtotal`, `igv` y `total`) se derivan de los hechos comerciales, por lo que siempre concuerdan entre sí y con los totales de cabecera.
- Las líneas exoneradas e inafectas se declaran con su tratamiento y se suman en `total_exonerada` y `total_inafecta`.
