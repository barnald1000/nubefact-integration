# Nubefact Integration

[![Tests](https://github.com/barnald1000/nubefact-integration/actions/workflows/tests.yml/badge.svg)](https://github.com/barnald1000/nubefact-integration/actions/workflows/tests.yml)

Emisión de facturas y boletas electrónicas a [Nubefact](https://www.nubefact.com) para PHP 8.2+, con todos los importes calculados por la librería.

```php
// La aplicación describe los hechos de cada línea
$line = Line::onerous(
    item     : Item::of('CUOTA-03', 'Cuota marzo', Unit::service()),
    quantity : Quantity::of(1),
    unitPrice: Amount::of('500.00'),         // con IGV, antes del descuento
    tax      : IGVTreatment::taxedIncluded(),
    discount : Amount::of('250.00'),         // beca del 50 %, con IGV
);

// La operación reúne la moneda, la tasa y las líneas; la librería calcula sus importes
$operation  = Operation::of(Currency::PEN, IgvRate::of(18), new Lines([$line]));
$projection = $operation->project();
$projection->totalTaxed();   // 211.86
$projection->totalTaxes();   // 38.14
$projection->total();        // 250.00

// El documento ($document implementa FinancialTransaction) entrega su operación y se envía a Nubefact
$processor = new TransactionProcessor(new PaymentsSender(new GuzzleNubeFactService($url, $token)), $logger);
$responses = $processor->process(FinancialTransactions::from([$document]));
```

## Instalación

```bash
composer require savia/nubefact-integration
```

Requiere PHP 8.2 o superior. Usa `guzzlehttp/guzzle` para HTTP, `brick/math` para la aritmética exacta, y `vaened/support` y `lambdish/phunctional` para colecciones.

El ejemplo asume que la aplicación ya implementa los contratos de documento y cliente.

## Principio

La librería no recibe importes calculados. Recibe los hechos comerciales de la operación y deriva de ellos todos los importes que pide Nubefact. Los ítems y la cabecera salen del mismo cálculo.

Solo se valida el dinero: importes, cantidades y descuentos. Nubefact no comprueba que los importes sean coherentes entre sí, pero sí valida el resto de los datos (cliente, serie, número, descripción, unidad de medida, RUC en facturas), y la librería los envía tal cual.

## Superficie de integración

La librería provee las líneas ([`Lines`](src/Lines)), la operación ([`Operation`](src/Operation.php)), las proyecciones con el cálculo ([`Projection`](src/Projection)), los esquemas del JSON ([`Actions/Payments`](src/Actions/Payments)), el envío ([`TransactionProcessor`](src/TransactionProcessor.php), [`GuzzleNubeFactService`](src/GuzzleNubeFactService.php)) y las respuestas ([`Responses`](src/Responses)).

La aplicación implementa el contrato del documento, el del cliente y, si lo necesita, el registro de respuestas.

### Fundamentos

- **FinancialTransaction**
    - Contrato: [`FinancialTransaction`](src/Contracts/FinancialTransaction.php)
    - Representa un comprobante: la identidad del documento más la operación que registra. Solo entrega hechos:
        - `id(): string` — identificador de la aplicación, usado para correlacionar respuestas.
        - `number(): int` y `serial(): string` — número y serie.
        - `type(): DocumentType` — [`DocumentType`](src/Keywords/DocumentType.php): `Invoice`, `Receipt`, `CreditNote`, `DebitNote`.
        - `customer(): Customer` — el comprador.
        - `issueDate(): DateTimeInterface` — fecha de emisión.
        - `operation(): Operation` — la [operación](#operación) del documento.

- **Customer**
    - Contrato: [`Customer`](src/Contracts/Customer.php), extiende [`Identifiable`](src/Contracts/Identifiable.php).
    - Provee `identificationType(): IdentificationType`, `identificationNumber(): string`, `denomination(): string`, `email(): ?string` y `address(): ?string`.
    - [`IdentificationType`](src/Keywords/IdentificationType.php): `Dni`, `Ruc`, `CE`, `Passport`.

- **LoggerRepository**
    - Contrato: [`LoggerRepository`](src/LoggerRepository.php)
    - Opcional. Recibe cada respuesta, `Success` o `Error`, en `log()`.

## Líneas

Una línea describe una operación onerosa con sus hechos:

```php
Line::onerous(
    item     : Item::of(code: 'LIB-01', description: 'Libro de álgebra', unit: Unit::product(), sunatCode: '55101500'),
    quantity : Quantity::of(3),
    unitPrice: Amount::of('80.00'),
    tax      : IGVTreatment::taxedIncluded(),
    discount : Amount::of('20.00'),
);
```

- [`Item`](src/Lines/Item.php) — código interno opcional, descripción, unidad y código de producto SUNAT opcional.
- [`Unit`](src/Lines/Unit.php) — `Unit::product()` (`NIU`), `Unit::service()` (`ZZ`) o `Unit::of($codigo)` para una unidad creada en la cuenta de Nubefact.
- [`Quantity`](src/Lines/Quantity.php) — entero mayor que cero.
- [`Amount`](src/Lines/Amount.php) — importe no negativo de hasta 2 decimales. No lleva moneda; la moneda es del documento.
- El descuento es opcional, se aplica a toda la línea y no puede superar `precio × cantidad`.

### Tratamientos de IGV

[`IGVTreatment`](src/Lines/IGVTreatment.php) indica cómo tributa la línea y en qué base vienen el precio y el descuento.

| Fábrica           | Significado                                                 | `tipo_de_igv` |
|-------------------|-------------------------------------------------------------|--------------:|
| `taxedIncluded()` | Gravada; el precio y el descuento ya incluyen IGV.          |             1 |
| `taxedExcluded()` | Gravada; el precio y el descuento no incluyen IGV; se suma. |             1 |
| `exonerated()`    | Exonerada; no hay IGV.                                      |             8 |
| `unaffected()`    | Inafecta; no hay IGV.                                       |             9 |

Una misma venta puede expresarse de las dos formas gravadas: `taxedIncluded()` con precio 118.00 y `taxedExcluded()` con precio 100.00 dan subtotal 100.00, IGV 18.00 y total 118.00.

`key()` devuelve la clave para persistir el tratamiento (`taxed_included`, `taxed_excluded`, `exonerated`, `unaffected`) e `IGVTreatment::fromKey()` lo reconstruye.

## Operación

[`Operation`](src/Operation.php) reúne los hechos que se calculan juntos: la moneda, la tasa de IGV y las líneas. Son inseparables, de modo que todas las líneas se calculan con la misma tasa que se envía en la cabecera. La operación existe antes que el documento: sus importes pueden calcularse al registrar la venta, sin serie ni número.

```php
$operation = Operation::of(Currency::PEN, IgvRate::of(18), $lines);
$operation->project();   // OperationProjection
```

- [`Currency`](src/Keywords/Currency.php): `PEN`, `USD`, `EUR`.
- [`IgvRate::of()`](src/Keywords/IgvRate.php) recibe un porcentaje (`int` o `float`) mayor que 0, de hasta 100 y con hasta 2 decimales, por ejemplo `IgvRate::of(18)` o `IgvRate::of(18.00)`. `IgvRate::standard()` es la tasa general de 18 %.
- [`Lines`](src/Lines/Lines.php) es la colección de líneas.

## Cálculo

Cada importe se calcula exacto y se redondea una vez, mitad hacia arriba. Los totales de cabecera son la suma de las líneas ya redondeadas.

| Tratamiento           | Subtotal                                 | IGV               | Total                           |
|-----------------------|------------------------------------------|-------------------|---------------------------------|
| Gravada, IGV incluido | `(precio × cantidad − descuento) ÷ 1.18` | `subtotal × 0.18` | `precio × cantidad − descuento` |
| Gravada, IGV excluido | `precio × cantidad − descuento`          | `subtotal × 0.18` | `subtotal + igv`                |
| Exonerada / inafecta  | `precio × cantidad − descuento`          | `0`               | `subtotal`                      |

- Con IGV incluido, el total es el importe cobrado.
- `valor_unitario` (sin IGV) y `precio_unitario` (con IGV) corresponden al precio antes del descuento y llevan hasta 10 decimales.
- `descuento` se envía antes de impuestos.

Sin redondeos de por medio, los resultados coinciden con el Excel de cálculo de Nubefact. Con divisiones inexactas, como 500 ÷ 1.18, el subtotal y el IGV pueden diferir en menos de un céntimo de las fórmulas del Excel, que encadenan valores ya redondeados; el total sigue siendo el importe cobrado. El detalle, con ejemplos, está en [`docs/CALCULO_FISCAL.md`](docs/CALCULO_FISCAL.md).

## Proyecciones

Una proyección contiene los importes calculados de una operación o de una línea, y solo se obtiene a partir de sus hechos.

- **OperationProjection**
    - Servicio: [`OperationProjection`](src/Projection/OperationProjection.php)
    - Se obtiene con `$operation->project()`. Calcula cada línea con la tasa de la operación y suma la cabecera. No tiene identidad de documento.
    - Expone `lines(): LineProjections`, `subtotal()` (suma de los subtotales de todas las líneas), `totalTaxed()`, `totalExonerated()`, `totalUnaffected()`, `totalDiscounts()`, `totalTaxes()` y `total()`.

- **LineProjection**
    - Servicio: [`LineProjection`](src/Projection/LineProjection.php)
    - La crea `OperationProjection` y se lee desde `lines()`. `LineProjection::of()` es público por limitación del lenguaje, pero es de uso interno.
    - Expone `subtotal()`, `totalTaxes()`, `total()`, `totalDiscounts()` (antes de IGV), `unitValue()`, `unitPrice()`, `igvType()`, `item()` y `quantity()`.

Ambas implementan [`Totalizable`](src/Contracts/Totalizable.php): `subtotal()`, `total()`, `totalDiscounts()` y `totalTaxes()`.

[`DocumentProjection`](src/Projection/DocumentProjection.php) es interna: compone el documento, su operación y la proyección de esa operación, pidiendo `operation()` una sola vez, para que la cabecera y los ítems salgan del mismo cálculo.

Los importes son `Brick\Math\BigDecimal` con 2 decimales. Se guardan con `(string) $importe` en columnas `DECIMAL(10,2)`, nunca como `float`.

Si la aplicación persiste los resultados, puede calcularlos al registrar la venta con `Operation::project()`. Antes de emitir conviene recalcularlos desde los hechos y no enviar el documento si difieren.

## Envío a Nubefact

```php
$service   = new GuzzleNubeFactService('https://api.nubefact.com/api/v1/RUTA', 'TOKEN');
$processor = new TransactionProcessor(new PaymentsSender($service), $logger);   // $logger es opcional

$responses = $processor->process(FinancialTransactions::from($documents));
```

- [`PaymentsSender`](src/Actions/Payments/PaymentsSender.php) obtiene la `DocumentProjection` del documento y arma el JSON con [`HeaderSchema`](src/Actions/Payments/HeaderSchema.php) para la cabecera y [`LineSchema`](src/Actions/Payments/LineSchema.php) para los ítems. Los esquemas traducen; no calculan.
- [`DecimalFormat`](src/DecimalFormat.php) decide cómo se escribe cada número en el JSON.
- [`TransactionProcessor`](src/TransactionProcessor.php) envía los documentos uno por uno, acumula las respuestas y las registra si recibe un `LoggerRepository`. Nubefact acepta un comprobante por solicitud.
- [`GuzzleNubeFactService`](src/GuzzleNubeFactService.php) hace el `POST` a la ruta con `Authorization: Token token=...` y agrega `operacion: generar_comprobante`.

## Respuestas

- **Success** — [`Success`](src/Responses/Success.php), cuando Nubefact responde con éxito HTTP.
    - `isAccept()` (`aceptada_por_sunat`), `hashCode()`, `qrCode()`, `barCode()`, `pdfDocument()`, `xmlDocument()`, `content()` y `transaction()`.
- **Error** — [`Error`](src/Responses/Error.php), cuando la solicitud falla con un error HTTP.
    - `isAccept()` (siempre `false`), `code()`, `messages()`, `error()` y `transaction()`.

## Errores

| Error                                          | Cuándo                                                                                                                                        |
|------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------|
| `InvalidLine` (`DomainException`)              | Un importe negativo o con más de 2 decimales, una cantidad no positiva, un precio cero, un descuento mayor que la línea o una clave de tratamiento desconocida. |
| `InvalidIgvRate` (`DomainException`)           | Una tasa de IGV menor o igual que 0, mayor que 100, no finita o con más de 2 decimales.                                             |
| `InvalidSafelistItem`                          | Una colección tipada (`Lines`, `LineProjections`, `FinancialTransactions`) recibe un elemento de otro tipo.                                     |
| `InvalidPaymentStructure` (`RuntimeException`) | A un esquema le falta una clave requerida por Nubefact.                                                                                       |
| `LogicException`                               | Una línea tiene un tipo de IGV sin total de cabecera soportado.                                                                               |

Los rechazos de Nubefact no son excepciones: llegan como `Error`, con su código y mensajes. Las excepciones de red distintas de `RequestException` no se capturan.

## Desarrollo

```bash
composer install
composer lint        # composer lint:fix para corregir
vendor/bin/phpunit
```

## Documentación adicional

- [`docs/CALCULO_FISCAL.md`](docs/CALCULO_FISCAL.md): cómo se calcula cada importe, con ejemplos.
- [`CHANGELOG.md`](CHANGELOG.md): cambios por versión.
- Reglas de Nubefact verificadas sobre una grilla de líneas: [`tests/Projection/NubefactRulesTest.php`](tests/Projection/NubefactRulesTest.php).
- Ejemplos del Excel de Nubefact reproducidos campo por campo: [`tests/Projection/NubefactSpreadsheetExamplesTest.php`](tests/Projection/NubefactSpreadsheetExamplesTest.php).
- Implementación mínima de `FinancialTransaction`: [`tests/Support/LinesTransaction.php`](tests/Support/LinesTransaction.php).
