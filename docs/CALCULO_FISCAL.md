# Cálculo fiscal de líneas y documentos

> **Estado:** este documento describe el cálculo implementado en `src/Lines` y `src/Projection` desde la versión 0.5.0. El uso de la API está en el [`readme.md`](../readme.md).
>
> **Alcance:** boletas y facturas, operaciones onerosas, importes de la academia. Quedan fuera notas de crédito y débito, operaciones gratuitas, IVAP, ISC, impuesto a bolsas, exportación, percepciones, retenciones, detracciones, descuentos globales y anticipos.

---

## 1. Qué problema resuelve esta librería

Nubefact acepta casi cualquier combinación de importes. Si una aplicación envía por separado precio, descuento, subtotal, IGV y totales, esos valores pueden contradecirse sin que nadie lo detecte. Eso ocurrió en producción: se envió un precio ya descontado junto con el descuento original.

La librería evita esa doble verdad:

- **Entrada:** solo *hechos comerciales* (qué se vendió, a qué precio, con qué descuento, con qué tratamiento de IGV).
- **Salida:** todos los importes derivados, calculados una sola vez, con una sola regla de redondeo.

La librería **no recibe resultados** (subtotal, IGV, total). Tampoco tiene base de datos: es lógica pura.

### Alcance de la regla: solo dinero

La librería hace **imposible un estado monetario inconsistente**: precios, descuentos, IGV, subtotales y totales solo se pueden obtener calculándolos desde los hechos. Esto es así porque Nubefact no valida la coherencia de los importes, como se comprobó en producción.

La regla **no** se aplica a los datos que no son dinero: identificación, nombre, dirección y email del cliente, serie, número, o la exigencia de RUC para emitir una factura. La librería no los valida: los envía tal cual, Nubefact los valida y su respuesta queda registrada. Un dato de cliente incorrecto no debe impedir que se emita un comprobante.

---

## 2. Hechos de entrada

| Dato | Pertenece a | Significado |
|---|---|---|
| Moneda | Documento | En qué moneda están todos los importes. |
| Tasa de IGV | Documento | Por ejemplo 18 %. Nubefact la recibe una sola vez, en `porcentaje_de_igv`. |
| Cantidad | Línea | Unidades vendidas. |
| Precio unitario | Línea | Importe por unidad **antes del descuento**. |
| Descuento | Línea | Reducción comercial **total de la línea** (no por unidad). |
| Tratamiento de IGV | Línea | Cuatro opciones, ver abajo. |

El precio unitario y el descuento son importes sin moneda propia y de 2 decimales. Ambos siempre están en la **misma base**: o los dos incluyen IGV, o los dos no lo incluyen. No se pueden mezclar.

### Tratamientos de IGV

| Tratamiento | Qué significa | `tipo_de_igv` Nubefact |
|---|---|---:|
| Gravada, importes con IGV incluido | El precio y el descuento ya contienen IGV. | 1 |
| Gravada, importes sin IGV | El precio y el descuento no contienen IGV; el IGV se suma. | 1 |
| Exonerada | No hay IGV en el importe ni se agrega. | 8 |
| Inafecta | No hay IGV en el importe ni se agrega. | 9 |

En exonerada e inafecta no existe "con IGV" ni "sin IGV": no hay impuesto que extraer ni agregar.

La tasa nunca forma parte de la línea. La aporta el documento.

---

## 3. Regla de redondeo

Toda la librería usa una única regla:

1. **Dentro de una línea no se redondea ningún valor intermedio.** Los cálculos usan precisión decimal completa.
2. **Cada importe de 2 decimales se redondea una sola vez**, a 2 decimales, con redondeo "mitad hacia arriba".
3. **Los totales del documento no se recalculan: se suman las líneas ya redondeadas.**

La regla 3 existe porque Nubefact exige que "la suma de cada línea del comprobante" coincida con los totales.

### Por qué no se redondea al final del documento

Con tres líneas de 10.00 con IGV incluido:

| Forma | Subtotal | IGV | Total | ¿Suma las líneas? |
|---|---:|---:|---:|---|
| Redondear por línea y sumar | 25.41 | 4.59 | 30.00 | Sí |
| Redondear al final del documento | 25.42 | 4.58 | 30.00 | No |

Cada línea, por separado: subtotal 8.47, IGV 1.53, total 10.00.

---

## 4. Fórmulas

Sea `P` el precio unitario, `Q` la cantidad, `D` el descuento de la línea y `r` la tasa de IGV (por ejemplo 0.18).

### 4.1 Gravada, importes con IGV incluido

```text
total    = (P × Q) − D                       exacto, 2 decimales
subtotal = redondeo(total ÷ (1 + r))
igv      = total − subtotal
```

El total es exactamente lo que se cobró. El IGV se obtiene por diferencia, de modo que `subtotal + igv = total` siempre.

### 4.2 Gravada, importes sin IGV

```text
subtotal = (P × Q) − D
igv      = redondeo(subtotal × r)
total    = subtotal + igv
```

### 4.3 Exonerada o inafecta

```text
subtotal = (P × Q) − D
igv      = 0
total    = subtotal
```

### 4.4 Documento

```text
total_gravada   = suma de subtotal de líneas gravadas
total_exonerada = suma de subtotal de líneas exoneradas
total_inafecta  = suma de subtotal de líneas inafectas
total_igv       = suma de igv de líneas
total           = suma de total de líneas
```

---

## 5. Cómo se proyecta a Nubefact

Nubefact define cada línea a partir del **valor unitario sin IGV**. Estas fórmulas son las de la hoja de cálculo oficial de Nubefact:

```text
valor_unitario  = dato de entrada, sin IGV
precio_unitario = valor_unitario × (1 + r)     precio ANTES del descuento
descuento       = descuento de la línea, ANTES de impuestos
subtotal        = cantidad × valor_unitario − descuento
igv             = subtotal × r
total           = subtotal + igv
```

Como nuestros hechos pueden venir con IGV incluido, la librería deriva lo que Nubefact pide:

```text
valor_unitario  = P ÷ (1 + r)                  hasta 10 decimales
precio_unitario = P                            (con IGV incluido) o P × (1 + r) (sin IGV)
descuento       = redondeo(D ÷ (1 + r))        (con IGV incluido); D (sin IGV)
```

`subtotal`, `igv` y `total` son los de la sección 4.

`valor_unitario` y `precio_unitario` se envían con hasta 10 decimales. **No se persisten**: se recalculan en cada proyección.

### Límites de decimales de Nubefact

| Campo | Decimales máximos |
|---|---:|
| `cantidad` | Nubefact admite 10; la librería exige un entero |
| `valor_unitario`, `precio_unitario` | 10 |
| `descuento`, `subtotal`, `igv`, `total` | 2 |

---

## 6. Ejemplos

Tasa de IGV: 18 %. Moneda: PEN.

### 6.1 Gravada, IGV incluido, sin descuento

Precio 590.00, cantidad 1.

| Campo | Valor |
|---|---:|
| total | 590.00 |
| subtotal | 500.00 |
| igv | 90.00 |
| valor_unitario | 500.0000000000 |
| precio_unitario | 590.00 |
| descuento | 0.00 |

### 6.2 Beca del 50 %: gravada, IGV incluido, con descuento

La mensualidad es 200.00 y el alumno debe pagar 100.00. Precio 200.00, descuento 100.00.

| Campo | Valor |
|---|---:|
| total | **100.00** |
| subtotal | 84.75 |
| igv | 15.25 |
| valor_unitario | 169.4915254237 |
| precio_unitario | 200.00 |
| descuento (Nubefact) | 84.75 |

El descuento de 100.00 con IGV equivale a 84.75 antes de IGV. El alumno paga exactamente 100.00.

### 6.3 El caso que falló en producción

Precio 534.10, descuento 289.10, cantidad 1.

| Campo | Valor |
|---|---:|
| total | 245.00 |
| subtotal | 207.63 |
| igv | 37.37 |
| valor_unitario | 452.6271186441 |
| precio_unitario | 534.10 |
| descuento | 245.00 |

El `precio_unitario` enviado es el **original** (534.10), nunca el posterior al descuento (245.00). Ese era el error.

### 6.4 Cantidad mayor que 1

Precio unitario 15.00, cantidad 3, descuento de línea 5.00 (con IGV incluido).

| Campo | Valor |
|---|---:|
| total | 40.00 (3 × 15.00 − 5.00) |
| subtotal | 33.90 |
| igv | 6.10 |
| valor_unitario | 12.7118644068 |
| descuento | 4.24 |

El descuento es de **toda la línea**, no por unidad.

### 6.5 Gravada, importes sin IGV

Precio 500.00, descuento 100.00, cantidad 1.

| Campo | Valor |
|---|---:|
| subtotal | 400.00 |
| igv | 72.00 |
| total | 472.00 |
| valor_unitario | 500.00 |
| precio_unitario | 590.00 |

Atención: aquí el cliente paga 472.00, no 400.00. El tratamiento determina qué significan los 100.00 de descuento.

### 6.6 Exonerada e inafecta

Precio 500.00, descuento 100.00, cantidad 1.

| Campo | Exonerada | Inafecta |
|---|---:|---:|
| subtotal | 400.00 | 400.00 |
| igv | 0.00 | 0.00 |
| total | 400.00 | 400.00 |
| `tipo_de_igv` | 8 | 9 |

### 6.7 Documento con tres líneas

Tres líneas de 10.00 con IGV incluido: ver la sección 3. Cada línea es subtotal 8.47, IGV 1.53 y total 10.00. El documento queda en gravada 25.41, IGV 4.59 y total 30.00.

---

## 7. Descuentos y becas

- Un descuento comercial, incluida una beca expresada como descuento, **sigue siendo una operación onerosa**: existe precio original y existe descuento. Ambos se conservan como hechos separados.
- Una beca del 100 % da total 0.00 e IGV 0.00, pero sigue siendo una operación onerosa con descuento.
- Una operación **gratuita** es otro concepto (sin cobro y con valor referencial) y no está implementada. No se simula con un descuento del 100 %.
- El descuento nunca puede superar `P × Q`.

---

## 8. Base legal y verificación

| Qué | Fuente | Estado |
|---|---|---|
| Campos, decimales y `tipo_de_igv` | Manual de integración JSON de Nubefact v3.0 | Verificado |
| Fórmulas de línea y cabecera | Hoja "Manual Cálculo JSON TXT" de Nubefact | Verificado |
| Valor de venta, IGV y precio con IGV por ítem | Guía de elaboración de documentos XML UBL 2.1 de SUNAT | Verificado |
| Regla de redondeo único por importe | Coincide con las 3 líneas del ejemplo con descuento de la guía de SUNAT | Verificado con ese ejemplo |
| Tolerancia que aplica Nubefact o SUNAT a un céntimo de diferencia | No está documentada | **Pendiente**: requiere prueba con una cuenta de Nubefact |

La hoja de Nubefact no usa funciones de redondeo y todos sus ejemplos son exactos. La regla de redondeo de este documento se apoya en el ejemplo oficial de SUNAT, no en una definición explícita de Nubefact.

---

## 9. Qué hace la librería y qué no

La librería:

- recibe hechos y calcula todos los importes derivados;
- entrega los resultados como `BigDecimal` con 2 decimales (nunca `float`), y los escribe como números en el JSON de Nubefact;
- genera la proyección en la estructura que Nubefact espera;
- no tiene base de datos ni estado externo.

La aplicación que la usa (Savia) es responsable de:

- guardar los hechos y, para lectura, los resultados;
- guardar la tasa de IGV de cada documento, no leerla de configuración al re-emitir;
- antes de emitir, recalcular desde los hechos y comparar con lo guardado; si no coinciden, **no enviar**;
- fijar la fecha de emisión y el correlativo (Nubefact exige que `fecha_de_emision` sea la fecha actual).
