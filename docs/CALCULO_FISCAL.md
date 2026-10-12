# Cálculo fiscal

Este documento describe cómo la librería deriva los importes de cada línea y de la cabecera a partir de los hechos comerciales del documento. El uso de la API está en el [`readme.md`](../readme.md).

## Alcance

Boletas y facturas con operaciones onerosas. No cubre notas de crédito y débito, operaciones gratuitas, IVAP, ISC, impuesto a las bolsas plásticas, exportación, percepciones, retenciones, detracciones, descuentos globales ni anticipos.

## Hechos de entrada

| Dato               | Pertenece a | Significado                                                          |
|--------------------|-------------|----------------------------------------------------------------------|
| Moneda             | Documento   | Moneda de todos los importes.                                        |
| Tasa de IGV        | Documento   | La define la aplicación; 18 % en el caso general. Nubefact la recibe en `porcentaje_de_igv`. |
| Cantidad           | Línea       | Unidades vendidas; un entero mayor que cero.                         |
| Precio unitario    | Línea       | Importe por unidad antes del descuento.                              |
| Descuento          | Línea       | Reducción de toda la línea, no por unidad.                           |
| Tratamiento de IGV | Línea       | Cómo tributa la línea y en qué base vienen el precio y el descuento. |

El precio unitario y el descuento tienen hasta 2 decimales y siempre están en la misma base: ambos incluyen IGV o ninguno lo incluye. La tasa no forma parte de la línea.

## Tratamientos de IGV

| Tratamiento           | Base del precio y del descuento    | `tipo_de_igv` |
|-----------------------|------------------------------------|--------------:|
| Gravada, IGV incluido | Incluyen IGV.                      |             1 |
| Gravada, IGV excluido | No incluyen IGV; el IGV se suma.   |             1 |
| Exonerada             | No hay IGV que extraer ni agregar. |             8 |
| Inafecta              | No hay IGV que extraer ni agregar. |             9 |

## Regla de redondeo

1. Dentro de una línea no se redondea ningún valor intermedio.
2. Cada importe de 2 decimales se redondea una sola vez, mitad hacia arriba.
3. Los totales del documento no se recalculan: son la suma de las líneas ya redondeadas.

La tercera regla responde a la exigencia de Nubefact de que la suma de las líneas coincida con los totales. Con tres líneas de 10.00 con IGV incluido:

| Forma                            | Subtotal |  IGV | Total | ¿Coincide con las líneas? |
|----------------------------------|---------:|-----:|------:|---------------------------|
| Redondear por línea y sumar      |    25.41 | 4.59 | 30.00 | Sí                        |
| Redondear al final del documento |    25.42 | 4.58 | 30.00 | No                        |

Cada línea, por separado, tiene subtotal 8.47, IGV 1.53 y total 10.00.

## Fórmulas

`P` es el precio unitario, `Q` la cantidad, `D` el descuento de la línea y `r` la tasa de IGV.

### Gravada, IGV incluido

```text
total    = P × Q − D
subtotal = redondeo((P × Q − D) ÷ (1 + r))
igv      = total − subtotal
```

El total es el importe cobrado, sin redondeo alguno.

### Gravada, IGV excluido

```text
subtotal = P × Q − D
igv      = redondeo(subtotal × r)
total    = subtotal + igv
```

### Exonerada e inafecta

```text
subtotal = P × Q − D
igv      = 0
total    = subtotal
```

### Documento

```text
subtotal        = Σ subtotal de todas las líneas
total_gravada   = Σ subtotal de las líneas gravadas
total_exonerada = Σ subtotal de las líneas exoneradas
total_inafecta  = Σ subtotal de las líneas inafectas
total_descuento = Σ descuento de las líneas, antes de IGV
total_igv       = Σ igv de las líneas
total           = Σ total de las líneas
```

`subtotal` es la suma de los subtotales de todas las líneas; `total_gravada`, `total_exonerada` y `total_inafecta` lo reparten por tipo de IGV, así que siempre suman lo mismo. Nubefact recibe el desglose, no el subtotal. Se cumple `total = subtotal + total_igv`.

## Campos de Nubefact

Nubefact define cada ítem a partir del valor unitario sin IGV. Las fórmulas de su hoja de cálculo son:

```text
precio_unitario = valor_unitario × (1 + r)
subtotal        = cantidad × valor_unitario − descuento
igv             = subtotal × r
total           = subtotal + igv
```

La librería obtiene esos campos de los hechos:

```text
valor_unitario  = P ÷ (1 + r) con IGV incluido; P en los demás casos
precio_unitario = P con IGV incluido; P × (1 + r) con IGV excluido; P en exonerada e inafecta
descuento       = redondeo(D ÷ (1 + r)) con IGV incluido; D en los demás casos
```

`subtotal`, `igv` y `total` son los de la sección anterior. `valor_unitario` y `precio_unitario` corresponden al precio antes del descuento y no se persisten: se recalculan en cada proyección.

| Campo                                   | Decimales                                    |
|-----------------------------------------|----------------------------------------------|
| `cantidad`                              | Nubefact admite 10; la librería exige entero |
| `valor_unitario`, `precio_unitario`     | Hasta 10                                     |
| `descuento`, `subtotal`, `igv`, `total` | 2                                            |

## Diferencia con la hoja de cálculo de Nubefact

La hoja de Nubefact encadena valores ya redondeados: calcula el subtotal con el `descuento` de 2 decimales y el IGV con el `subtotal` de 2 decimales. La librería calcula cada importe desde los valores exactos. Cuando no hay nada que redondear, ambos procedimientos dan el mismo resultado; los ejemplos de la hoja se reproducen campo por campo.

Con divisiones inexactas pueden diferir en menos de un céntimo. Una cuota de 500.00 con IGV incluido y una beca de 250.00:

| Campo            | Librería       | Fórmula literal de la hoja                      |
|------------------|----------------|-------------------------------------------------|
| `valor_unitario` | 423.7288135593 | —                                               |
| `descuento`      | 211.86         | —                                               |
| `subtotal`       | 211.86         | 1 × 423.7288135593 − 211.86 = 211.8688 → 211.87 |
| `igv`            | 38.14          | 211.86 × 0.18 = 38.1348 → 38.13                 |
| `total`          | 250.00         | 249.99 o 250.01, según el subtotal que se tome  |

Con las fórmulas literales de la hoja no existe un subtotal que dé un total de 250.00. La librería conserva el total cobrado; el subtotal y el IGV se apartan menos de un céntimo de esas fórmulas.

## Ejemplos

Tasa de IGV de 18 % y moneda PEN.

### Gravada, IGV incluido, sin descuento

Precio 590.00, cantidad 1.

| Campo           |  Valor |
|-----------------|-------:|
| valor_unitario  | 500.00 |
| precio_unitario | 590.00 |
| descuento       |   0.00 |
| subtotal        | 500.00 |
| igv             |  90.00 |
| total           | 590.00 |

### Gravada, IGV incluido, con descuento del 50 %

Precio 200.00, descuento 100.00, cantidad 1.

| Campo           |          Valor |
|-----------------|---------------:|
| valor_unitario  | 169.4915254237 |
| precio_unitario |         200.00 |
| descuento       |          84.75 |
| subtotal        |          84.75 |
| igv             |          15.25 |
| total           |         100.00 |

### Gravada, IGV incluido, con descuento parcial

Precio 534.10, descuento 289.10, cantidad 1.

| Campo           |          Valor |
|-----------------|---------------:|
| valor_unitario  | 452.6271186441 |
| precio_unitario |         534.10 |
| descuento       |         245.00 |
| subtotal        |         207.63 |
| igv             |          37.37 |
| total           |         245.00 |

`precio_unitario` es el precio original, no el resultante del descuento.

### Cantidad mayor que uno

Precio 15.00, cantidad 3, descuento de línea 5.00, IGV incluido.

| Campo          |         Valor |
|----------------|--------------:|
| valor_unitario | 12.7118644068 |
| descuento      |          4.24 |
| subtotal       |         33.90 |
| igv            |          6.10 |
| total          |         40.00 |

### Gravada, IGV excluido

Precio 500.00, descuento 100.00, cantidad 1.

| Campo           |  Valor |
|-----------------|-------:|
| valor_unitario  | 500.00 |
| precio_unitario | 590.00 |
| descuento       | 100.00 |
| subtotal        | 400.00 |
| igv             |  72.00 |
| total           | 472.00 |

Con el mismo precio y descuento, el total difiere del caso con IGV incluido: el tratamiento determina la base de ambos importes.

### Exonerada e inafecta

Precio 500.00, descuento 100.00, cantidad 1.

| Campo         | Exonerada | Inafecta |
|---------------|----------:|---------:|
| subtotal      |    400.00 |   400.00 |
| igv           |      0.00 |     0.00 |
| total         |    400.00 |   400.00 |
| `tipo_de_igv` |         8 |        9 |

## Descuentos y becas

- Un descuento comercial, incluida una beca expresada como descuento, es una operación onerosa: existen un precio original y un descuento.
- Un descuento del 100 % produce total 0.00 e IGV 0.00 y sigue siendo una operación onerosa.
- Una operación gratuita es un concepto distinto, con valor referencial y sin cobro, y no está implementada.
- El descuento no puede superar `P × Q`.

## Fuentes y verificación

| Regla                                              | Fuente                                                 | Verificación                                                                                                                                                      |
|----------------------------------------------------|--------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Campos, decimales y `tipo_de_igv`                  | Manual de integración JSON de Nubefact v3.0            | [`KeywordsTest`](../tests/Keywords/KeywordsTest.php), [`LineSchemaTest`](../tests/Actions/Payments/LineSchemaTest.php)                                            |
| Fórmulas de línea y cabecera                       | Hoja "Manual Cálculo JSON TXT" de Nubefact             | [`NubefactRulesTest`](../tests/Projection/NubefactRulesTest.php), [`NubefactSpreadsheetExamplesTest`](../tests/Projection/NubefactSpreadsheetExamplesTest.php) |
| Valor de venta, IGV y precio con IGV por ítem      | Guía de elaboración de documentos XML UBL 2.1 de SUNAT | El redondeo único reproduce el ejemplo con descuento de la guía                                                                                                   |
| Tolerancia ante diferencias de menos de un céntimo | No documentada por Nubefact ni SUNAT                   | Pendiente de confirmar con comprobantes reales                                                                                                                    |
