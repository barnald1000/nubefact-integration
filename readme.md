# NubeFact Integration Library

This library allows you to easily send payment to the NubeFact platform.

## Features

- Integration with the NubeFact API using Guzzle.
- Batch processing of transactions.
- Handling of NubeFact responses.
- Easy to use and extensible.

## Requirements

- PHP 8.2 or higher
- Guzzle library installed

## Usage

Here's a basic example of how to use the library:

```php
$requests = $this->repository->getAllRequests();

$service   = new GuzzleNubeFactService('https://api.nubefact.com', 'token');
$sender    = new PaymentsSender($service);
$processor = new TransactionProcessor($sender);

$transactions = $requests->map(static fn(array $raw) => new SingleEntryItemTransaction($raw));

$responses = $processor->process($transactions);

// Do something with the NubeFact responses
foreach ($responses as $response) {
    if ($response instanceof Success) {
        // Process the successful response
    } else {
        // Handle errors
    }
}
```

