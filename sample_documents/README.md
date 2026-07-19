# Sample Documents

Pre-built documents for testing ingestion into the RAG index.

## Ingest

```bash
# All files
php artisan rag:ingest sample_documents

# Single file
php artisan rag:ingest sample_documents/faq.txt
```

## Files

| File | Content |
|------|---------|
| `meeting-notes.txt` | Team meeting summary |
| `product-overview.txt` | Product description and use cases |
| `faq.txt` | Common questions and answers |
