# Blueline API Documentation

## Overview

The Blueline API provides programmatic access to a library of bell-ringing methods, collections, and performance records. The API is **read-only** and returns data as JSON.

## Quick Start

Fetch a method by its URL identifier:

```bash
curl https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major.json
```

Response:
```json
[{
  "title": "Cambridge Surprise Major",
  "stage": 8,
  "classification": "Surprise",
  "notation": "x38x14x1258x36x14x58x16x78,12",
  "url": "Cambridge_Surprise_Major",
  ..............
}]
```

## Base URL & Content Negotiation

Blueline uses HTTP content negotiation via file extension (suffix) to determine response format. Add `.json` to any endpoint path to request JSON output. Some endpoints also support PNG, XML, or CSV.

```
Base: https://rsw.me.uk/blueline
JSON endpoints: /path.json
PNG endpoints: /path.png
CSV endpoints: /path.csv
XML endpoints: /path.xml
```

## Authentication & Access

**No authentication required.** All API endpoints are public and read-only.

**Public-facing access** is currently provided via Cloudflare's free tier and may be rate-limited or deprioritised according to their policies.

**Responses are cached** for a period of time (`Cache-Control: public, max-age=XXXXX`). To bypass caching, add a unique query parameter (e.g., `?cache_bust=12345`) to your requests.

**I am generally OK with all use of the service**, and it should handle all sensible usage but please consider:

- Implementing request caching on your client
- Using `If-Modified-Since` headers to leverage Cloudflare-side caching
- Not sending multiple parallel requests, particularly for PNG image generation

## Endpoints Reference

### GET /methods/view/{url}.json

Retrieve a single method by its URL identifier, including related methods that differ only at specific points.

#### Parameters:

| Name | Required | Type | Description |
|------|----------|------|-------------|
| `url` | Yes | String | URL identifier (e.g., `Cambridge_Surprise_Major`). Use canonical URL form with underscores replacing spaces and proper capitalization. Invalid or non-existent URLs redirect (301) or return 404. |

#### Response:

Returns an array containing one method object with extended relationship data:

```json
[{
  "title": "Cambridge Surprise Major",
  "abbreviation": "C",
  "stage": 8,
  "classification": "Surprise",
  "notation": "x38x14x1258x36x14x58x16x78,12",
  "notationExpanded": "x38x14x1258x36x14x58x16x78x16x58x14x36x1258x14x38x12",
  "leadHead": "15738264",
  "leadHeadCode": "b",
  "fchGroups": "BDEe",
  ..............
}]
```

#### Characteristics:

- Response is always an array (for API consistency)
- `similar` object groups related methods by type of difference
- Empty arrays in `similar` indicate no matches in that category
- All fields may be `null` if data is not available. In the source data from the CCCBR null often implies false.

#### Example:

```bash
curl 'https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major.json'
```

---

### GET /methods/view/{url}.png

Retrieve a PNG diagram/image of a method showing its structural pattern and notation.

#### Parameters:

| Name | Required | Type | Description |
|------|----------|------|-------------|
| `url` | Yes | String | URL identifier (e.g., `Cambridge_Surprise_Major`). Use canonical URL form with underscores replacing spaces and proper capitalization. Invalid or non-existent URLs redirect (302) or return 404. |
| `scale` | No | Integer | Image scale factor (default: `1`). Larger values produce bigger diagrams; useful for high-resolution displays. |
| `style` | No | String | Diagram style. `numbers`, `lines`, `grid`, `diagrams`. Default: `numbers`. |

#### Response:

Returns a PNG image binary.

#### Behavior:

- Content-Type is `image/png`
- Responses include `Content-Disposition: attachment` header (triggers download in browsers with a filename like `Cambridge_Surprise_Major.png`)
- Invalid or non-existent URLs trigger a temporary redirect (302) or return 404
- Generation is on-demand; large diagrams may take a moment to render

#### Example:

```bash
# Fetch and save method diagram
curl -L 'https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major.png' -o Cambridge_Surprise_Major.png

# With custom styling
curl -L 'https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major.png?scale=4&style=grid' -o diagram_large.png
```

---

### GET /methods/view.(json|png)

Parse and generate a response for a custom or ad-hoc method notation. Useful for exploring methods not yet in the database.

#### Parameters:

| Name | Required | Type | Description |
|------|----------|------|-------------|
| `notation` | Yes | String | Place notation (e.g., `x38x14x1258x36x14x58x16x78,12` or `3,1.5.1.5.1`). Supports multiple notation styles. |
| `stage` | No | Integer | Number of bells (3–12+). Auto-detected from notation if omitted. |
| `title` | No | String | Custom title for the method. If omitted, title is auto-generated. |

#### Response:

Returns an array with one synthetic method object. Or an image of it.

```json
[{
  "title": "Cambridge Surprise Major;",
  "stage": 8,
  "notation": "x38x14x1258x36x14x58x16x78",
  "notationExpanded": "x38x14x1258x36x14x58x16x78",
  "leadHead": "74256831",
  "lengthOfLead": 16,
  "lengthOfCourse": 128,
  "classification": "Surprise",
  "similar": null,
  ...
}]
```

#### Behavior:

- If the notation matches an existing database method, the request redirects (301) to `/methods/view/{url}.json` for that method
- Custom methods do not include similarity data or database-specific fields
- Stage is guessed from the bell characters present in notation if not provided

#### Example:

```bash
curl 'https://rsw.me.uk/blueline/methods/view.json?notation=x38x14x1258x36x14x58x16x78&stage=8&title=Cambridge%20Surprise%20Major'
```

---

### GET /methods/search.json

Search for methods by query string, with optional filtering by stage, classification, and other properties.

#### Parameters:

| Name | Required | Type | Description |
|------|----------|------|-------------|
| `q` | No | String | Search query. Supports plain text (searches title and phonetically), regex patterns (must start with `/`, e.g., `/^Cambridge/`), or stage + classification filtering. |
| `stage` | No | Integer | Filter by number of bells (e.g., `stage=6`). Can also be appended to `q` (e.g., `q=surprise 6` infers stage 6). |
| `classification` | No | String | Filter by method classification (e.g., `classification=Surprise`). Case-insensitive. |
| `little` | No | Boolean | Filter to little methods (e.g., `little=true`). |
| `differential` | No | Boolean | Filter to differential methods. |
| `fields` | No | String | Comma-separated field names to return (e.g., `fields=title,url,stage`). If omitted, defaults include title, abbreviation, url, classification, stage, notation, ruleOffs, calls, callingPositions, and cccbrId. |
| `sort` | No | String | Sort order. Default is `magic` (a calculated ranking based on how likely it is the user is searching for each method); other values: `title`, `stage`, etc. |
| `limit` | No | Integer | Maximum results per page. Default: 10. |
| `offset` | No | Integer | Results offset (for pagination). Default: 0. |

#### Response:

Returns an envelope with query metadata, result array, and total count:

```json
{
  "query": {
    "q": "cambridge surprise major",
    "fields": "title,url,stage,classification",
    "order": "ASC",
    "offset": 0,
    "count": 15,
    "sort": "magic"
  },
  "results": [
    { "title": "Cambridge Surprise Major", "url": "Cambridge_Surprise_Major", "stage": 8, "classification": "Surprise" },
    { "title": "Old Cambridge Surprise Major", "url": "Old_Cambridge_Surprise_Major", "stage": 8, "classification": "Surprise" },
    { "title": "Kimmeridge Surprise Major", "url": "Kimmeridge_Surprise_Major", "stage": 8, "classification": "Surprise" }
  ],
  "count": 9
}
```

#### Characteristics:

- Plain text queries use LIKE matching on the string, and phonetic matching to implement basic spell checking.
- Regex queries must be valid regex (checked server-side; returns 400 for invalid patterns)
- Search is case-insensitive
- Field filtering allows fetching only required data

#### Example:

```bash
# Simple text search
curl 'https://rsw.me.uk/blueline/methods/search.json?q=cambridge'

# Regex search
curl 'https://rsw.me.uk/blueline/methods/search.json?q=/^Cambridge.*Major/'

# Filtered search with field selection
curl 'https://rsw.me.uk/blueline/methods/search.json?q=surprise&stage=6&fields=title,url'

# Pagination
curl 'https://rsw.me.uk/blueline/methods/search.json?q=major&limit=50&offset=100'
```

#### Pagination:

Search results support cursor-based pagination:

```bash
# Fetch first 50 results
curl 'https://rsw.me.uk/blueline/methods/search.json?q=major&limit=50&offset=0'

# Fetch next 50
curl 'https://rsw.me.uk/blueline/methods/search.json?q=major&limit=50&offset=50'
```

#### Field Selection:

Reduce bandwidth by requesting only required fields:

```bash
# Fetch only essential data
curl 'https://rsw.me.uk/blueline/methods/search.json?q=cambridge&fields=title,url,stage'

# Response only includes: title, url, stage
```

#### Regex Search:

Advanced searches using regular expressions:

```bash
# Methods starting with "Cambridge"
curl 'https://rsw.me.uk/blueline/methods/search.json?q=/^Cambridge/'

# Methods with "Surprise" or "Treble"
curl 'https://rsw.me.uk/blueline/methods/search.json?q=/Surprise|Treble/'

# Invalid regex returns 400
curl 'https://rsw.me.uk/blueline/methods/search.json?q=/[/'  # Bad regex
```

---

### GET /services/notation.json

Parse and expand bell-ringing place notation into canonical forms.

#### Parameters:

| Name | Required | Type | Description |
|------|----------|------|-------------|
| `notation` | Yes | String | Place notation string (e.g., `x38x14x1258x36x14x58x16x78`, `3,1.5.1.5.1`, or `-1-1-1 le2`). Supports multiple notation conventions. |
| `stage` | No | Integer | Number of bells. Auto-detected from notation if omitted. |

#### Response:

Returns an object with parsed notation in three canonical forms:

```json
{
  "stage": 8,
  "expanded": "x38x14x1258x36x14x58x16x78",
  "siril": "&-3, +4, &-25, +36, &-4, +5, &-6, +7"
}
```

#### Fields:

| Field | Description |
|-------|-------------|
| `stage` | Detected or specified number of bells. |
| `expanded` | Expanded place notation (e.g., `x16x16...` for standard SiRIL form, or `3.1.5.1.5.1` for lower-stage). |
| `siril` | SIRIL notation (abbreviation style), using `+` for hand strokes and `&` or `-` for back strokes. |

#### Behavior:

- Stage is guessed from bell numbers present in notation if not explicitly provided
- Supports multiple input formats (legacy, SiRIL, ASCII, UTF-8 ampersand)
- Returns 400 if `notation` parameter is missing
- Cached for 3.6 days (129600 seconds)

#### Example:

```bash
# Auto-detect stage
curl 'https://rsw.me.uk/blueline/services/notation.json?notation=3,1.5.1.5.1'
# Returns: { "stage": 5, "expanded": "3.1.5.1.5.1.5.1.5.1", "siril": "+3, &1.5.1.5.1" }

# Explicit stage
curl 'https://rsw.me.uk/blueline/services/notation.json?notation=x38x14x1258x36x14x58x16x78&stage=8'
# Returns: { "stage": 8, "expanded": "x38x14x1258x36x14x58x16x78", "siril": "&-3, +4, &-25, +36, &-4, +5, &-6, +7" }
```

---

### GET /services/oembed.json

Retrieve oEmbed metadata for embedding method detail pages in third-party sites. Implements the [oEmbed 1.0 specification](https://oembed.com/).

#### Parameters:

| Name | Required | Type | Description |
|------|----------|------|-------------|
| `url` | Yes | String | Full URL of the method detail page (e.g., `https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major`). Must match the API's base domain. |

#### Response:

Current JSON response for this method URL:

```json
{
  "type": "https://tools.ietf.org/html/rfc2616#section-10",
  "title": "An error occurred",
  "status": 500,
  "detail": "Internal Server Error"
}
```

#### Fields:

| Field | Description |
|-------|-------------|
| `type` | Always `"photo"`. |
| `version` | oEmbed spec version (`"1.0"`). |
| `title` | Method title. |
| `provider_name` | Always `"Blueline"`. |
| `provider_url` | Link to API provider homepage. |
| `url` | PNG image URL (method diagram). Includes `scale` and `style` parameters. |
| `width` | Image width in pixels. |
| `height` | Image height in pixels. |

#### Behavior:

- Only JSON response format is supported (XML returns 501)
- URL must be a valid method detail page; invalid URLs return 500 or 404
- The returned image URL includes `scale=1&style=numbers` by default (can be customized)
- Useful for embedding method diagrams in social media posts, blogs, wikis, and documentation

#### Example:

```bash
curl 'https://rsw.me.uk/blueline/services/oembed.json?url=https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major'
```

---

### GET /data/{table}.csv

Export database tables as CSV for analysis and bulk import. Responses are streamed for memory efficiency.

#### Parameters:

| Name | Required | Type | Description |
|------|----------|------|-------------|
| `table` | Yes | String | Table name. Allowed values: `collections`, `methods`, `methods_collections`, `methods_similar`, `performances`. |

#### Supported Tables:

| Table | Description | Columns |
|-------|-------------|---------|
| `collections` | Method collections/groupings | id, name, description |
| `methods` | Complete method library | All Method entity fields (see Field Reference below) |
| `methods_collections` | Method-to-collection membership mapping | collection_id, position, method_title |
| `methods_similar` | Method similarity scores and flags | method1_title, method2_title, stage, similarity, onlydifferentoverleadend |
| `performances` | Documented bell-ringing performances | Links method to performance records |

#### Response:

CSV-formatted data, ordered by relevant keys (e.g., methods by stage, then classification, then title).

#### Behavior:

- Content-Type is `text/csv`
- Responses include `Content-Disposition: attachment` header (triggers download in browsers)
- Caching respects database update timestamps (7-day max-age)
- Large tables are streamed; suitable for production use
- Useful for data analysis, backups, and integrations

#### Example:

```bash
# Download methods table
curl -O 'https://rsw.me.uk/blueline/data/methods.csv'

# Download collections
curl 'https://rsw.me.uk/blueline/data/collections.csv' | head -20
```

---

## Field Reference

### Method Entity

The Method entity has ~40 fields. Common fields are listed below; refer to API responses for the complete set.

Method attributes are defined according to [v2 of the CCCBR's Framework for Method Ringing](https://framework.cccbr.org.uk/version2/).

| Field | Type | Description |
|-------|------|-------------|
| `title` | String | Method name (e.g., `"Cambridge Surprise Major"`). |
| `url` | String | URL identifier; unique, canonical form (e.g., `"Cambridge_Surprise_Major"`). |
| `abbreviation` | String \| null | Short abbreviation if applicable (e.g., `"C"`). |
| `stage` | Integer | Number of bells (3–12+). |
| `classification` | String \| null | Method type (e.g., `"Surprise"`, `"Treble Place"`, `"Principle"`). |
| `notation` | String | Place notation (input form, may be abbreviated). |
| `notationExpanded` | String | Fully expanded place notation. |
| `leadHead` | String | Lead head change (permutation of bells at end of lead). |
| `leadHeadCode` | String \| null | Named code for lead head formula. |
| `lengthOfLead` | Integer | Row count per lead. |
| `lengthOfCourse` | Integer | Row count per course. |
| `numberOfLeads` | Integer \| null | Leads per course. |
| `numberOfHunts` | Integer \| null | Number of hunting bells. |
| `magic` | Integer \| null | A calculated ranking based on how likely it is the user is searching for each method. |
| `calls` | Array \| null | Recognised calls (e.g., `["bob", "single"]`). |
| `ruleOffs` | Array \| null | Rule-off points/names. |
| `callingPositions` | Array \| null | Calling positions. |
| `cccbrId` | String \| null | Central Council of Church Bell Ringers (CCCBR) identifier. Used on Complib, and other places. |
| `methodReferences` | String \| null | References and sources. |
| `extensionConstruction` | String \| null | Notes on how the method extends to higher stages. |
| `provisional` | Boolean \| null | Whether method status is provisional/unconfirmed. |
| `little` | Boolean \| null | Whether method is little. |
| `differential` | Boolean \| null | Whether method is differential. |
| `plain` | Boolean \| null | Whether method is plain hunting. |
| `trebleDodging` | Boolean \| null | Whether treble dodges. |
| `palindromic` | Boolean \| null | Whether notation is palindromic. |
| `doubleSym` | Boolean \| null | Whether notation has double symmetry. |
| `rotational` | Boolean \| null | Whether notation is rotationally symmetric. |

### Collection Entity

Represents a curated grouping of methods.

| Field | Type | Description |
|-------|------|-------------|
| `name` | String | Collection name (e.g., `"Surprise Methods"`). |
| `description` | String | Text description. |

### Performance Entity

Documents a bell-ringing performance/ringing record.

| Field | Type | Description |
|-------|------|-------------|
| `methodTitle` | String | Title of the rung method. |
| `date` | Date | Date of performance. |
| `location` | String | Church or location name. |
| `bellsRung` | Integer | Number of bells rung. |
| Details | ... | Additional performance metadata. |

---

## Error Handling

All errors return appropriate HTTP status codes with JSON error details (when applicable).

| Status | Condition |
|--------|-----------|
| `200` | Successful request. |
| `301` | Permanent redirect (e.g., canonical URL form or renamed method). Follow Location header. |
| `302` | Temporary redirect (e.g., PNG rendering). Follow Location header. |
| `400` | Bad request (missing required parameter, invalid regex, etc.). Client error; review parameters. |
| `404` | Not found (method does not exist, invalid table in CSV export). |
| `500` | Server error (unexpected condition, validation failure). Rare; check URL validity. |
| `501` | Not implemented (e.g., oEmbed XML format). |


### Client Libraries

Any HTTP client supporting JSON is compatible:

**JavaScript (fetch API):**
```javascript
fetch('https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major.json')
  .then(res => res.json())
  .then(data => console.log(data[0].title));
```

**Python (requests):**
```python
import requests
r = requests.get('https://rsw.me.uk/blueline/methods/search.json', params={'q': 'cambridge surprise major', 'fields': 'title,url,stage,classification'})
print(r.json()['results'])
```

**cURL (as documented above):**
```bash
curl 'https://rsw.me.uk/blueline/methods/search.json?q=major&fields=title,stage'
```
