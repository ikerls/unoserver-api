# Unoserver API

A REST API for converting documents to PDF using LibreOffice via unoserver. Features memory-efficient streaming, comprehensive LibreOffice PDF export options, and Docker-based deployment.

> [!WARNING]
> **Security Notice**
>
> It is **NOT RECOMMENDED** to expose this service directly to the internet. This should be deployed in a private network or behind authentication/authorization layers.

## Features

- **Universal Document Conversion** - DOCX, XLSX, PPTX, ODT, ODS, ODP, images, and more to PDF
- **PDF Export Options** - Complete LibreOffice PDF export control including:
  - Watermarks (text and tiled)
  - Password encryption and permissions
  - Page ranges and selection
  - PDF/A compliance (1b, 2b, 3b)
  - Accessibility (PDF/UA and tagged PDFs)
  - Image compression and quality control
  - Bookmarks, forms, notes, and hidden slides
- **Smart Conversion Modes** - Automatic stream mode (≤10MB) or filesystem mode (>10MB) for optimal performance
- **OpenAPI Documentation** - OpenApi at `/openapi` with complete API specs
- **Production Ready** - Docker-based deployment with unoserver and LibreOffice pre-configured
- **Memory Efficient** - Streams PDF output in chunks
- **Health Checks** - Built-in `/health` endpoint for monitoring and load balancers

**Supported Formats:**
- **Documents:** DOCX, DOC, ODT, RTF, TXT, HTML
- **Spreadsheets:** XLSX, XLS, ODS, CSV
- **Presentations:** PPTX, PPT, ODP
- **Images:** PNG, JPG, JPEG, TIFF
- **PDF:** PDF (for applying options/re-processing existing PDFs)

## Installation

### Docker Compose

Create a `compose.yaml` file:

```yaml
services:
  php:
    image: ghcr.io/ikerls/unoserver-api:latest
    ports:
      - "8088:8080"
    environment:
      UNOSERVER_TIMEOUT: 120
      UPLOAD_MAX_SIZE: 100M
```

Start the service:

```bash
docker compose up -d

# View logs
docker compose logs -f

# Stop service
docker compose down
```

### Docker Run

```bash
docker run -d \
  --name unoserver-api \
  -p 8080:8080 \
  -e UNOSERVER_TIMEOUT=120 \
  -e UPLOAD_MAX_SIZE=100M \
  ghcr.io/ikerls/unoserver-api:latest
```

## API Documentation

### Endpoints

| Endpoint | Method | Description                                       |
|----------|--------|---------------------------------------------------|
| `/health` | GET | Health check endpoint (returns `{"status":"ok"}`) |
| `/convert` | POST | Convert document to PDF with options              |
| `/openapi` | GET | OpenApi file                                      |

### Basic Conversion Request

```bash
curl -X POST http://localhost:8080/convert \
  -F "file=@document.docx" \
  -o output.pdf
```
_[More examples](https://github.com/ikerls/unoserver-api/wiki/Usage-Examples)_

**Parameters:**
- `file` (required): Document file
- [LibreOffice PDF export options](https://github.com/ikerls/unoserver-api/wiki/Available-parameters) as form fields

## Environment Variables

Configure the service using environment variables:

| Variable | Default | Description |
|----------|---------|-------------|
| `UNOCONVERT_BINARY` | `unoconvert` | Path to the unoconvert binary |
| `UNOSERVER_HOST` | `127.0.0.1` | Host where unoserver daemon runs |
| `UNOSERVER_PORT` | `2003` | Port for unoserver connection |
| `UNOSERVER_TIMEOUT` | `120` | Maximum conversion time in seconds |
| `UNOSERVER_STREAM_THRESHOLD` | `10485760` | File size (bytes) to switch from stream to filesystem mode (default: 10MB) |
| `UPLOAD_MAX_SIZE` | `100M` | Maximum upload file size (e.g., `50M`, `200M`) |

**Setting variables:**

Via Docker Compose:
```yaml
services:
  php:
    environment:
      UNOSERVER_TIMEOUT: 300
      UPLOAD_MAX_SIZE: 150M
      UNOSERVER_STREAM_THRESHOLD: 20971520  # 20MB
```

Via Docker Run:
```bash
docker run -e UNOSERVER_TIMEOUT=300 -e UPLOAD_MAX_SIZE=150M ...
```

## License

This project uses LibreOffice and unoserver. Please review their respective licenses:
- [LibreOffice License (MPL 2.0)](https://www.libreoffice.org/about-us/licenses/)
- [unoserver License (MIT)](https://github.com/unoconv/unoserver)
