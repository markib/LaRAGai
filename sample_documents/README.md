# Sample Documents for Ingestion

These files are ready to ingest into the RAG index using the Laravel command:

```bash
php artisan rag:ingest sample_documents
```

You can also ingest a single file:

```bash
php artisan rag:ingest sample_documents/product-overview.txt
```

The sample documents include:

- `meeting-notes.txt` — a short team meeting summary.
- `product-overview.txt` — a product description and use cases.
- `faq.txt` — common questions and answers.
