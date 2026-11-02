# API reference

**Purpose:** Document Blueline's public read-only HTTP endpoints, their parameters, response fields and cache behavior.

**Audience:** API consumers and maintainers.

**Prerequisites:** None. Requests use the public site URL or a local Blueline installation.

## Overview

The API exposes method search and detail views, place-notation conversion, oEmbed metadata and CSV data exports. Endpoints are public and do not require authentication. The route suffix selects the response format; JSON is not selected through an `Accept` header alone.

The public deployment may be subject to CDN rate limits. Cache responses where practical and avoid unnecessary parallel requests, particularly for PNG rendering.

## Base URL and formats

The public base URL is `https://rsw.me.uk/blueline`. Use the corresponding base URL for another installation.

| Suffix | Format |
| --- | --- |
| `.json` | JSON |
| `.png` | PNG image |
| `.csv` | CSV export |
| `.txt` | Plain text notation output |
| `.xml` | XML route where available; oEmbed XML is not implemented. |

## Method endpoints

### `GET /methods/search.json`

Search methods and return a JSON envelope containing the query values, results and total match count.

#### Parameters

| Name | Required | Type | Description |
| --- | --- | --- | --- |
| `q` | No | String | Search method names by text or phonetic match. A multi-character value beginning with `/` is treated as a regular expression against the title; an invalid expression returns `400`. A trailing stage, classification, `little` or `differential` term can also be interpreted as a filter. |
| `stage` | No | Integer | Filter by number of bells, for example `stage=6`. |
| `classification` | No | String | Filter by classification, for example `classification=Surprise`. |
| `little` | No | Boolean | Filter the `little` field. |
| `differential` | No | Boolean | Filter the `differential` field. |
| `fields` | No | String | Comma-separated camelCase field names to return, for example `fields=title,url,notationSiril`. Unknown names are ignored; if none are recognized, the default field set is used. |
| `sort` | No | String | Method field to sort by. Defaults to `magic`. |
| `order` | No | String | `ASC` or `DESC`; defaults to `ASC`. |
| `count` | No | Integer | Maximum results per page. Defaults to `15`. |
| `offset` | No | Integer | Number of results to skip. Defaults to `0`. |

The API uses `count` and `offset` for pagination; it does not read a `limit` parameter.

#### Response

The top-level `count` is the total number of matching methods. `query.count` is the requested page size, not the total.

The following is an illustrative, intentionally abbreviated response shape; it is not a sample captured from a live service. See the [method field reference](#method-json-fields) for the complete field list.

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
    {
      "title": "Cambridge Surprise Major",
      "url": "Cambridge_Surprise_Major",
      "stage": 8,
      "classification": "Surprise"
    }
  ],
  "count": 1
}
```

#### Examples

```sh
# Text search
curl 'https://rsw.me.uk/blueline/methods/search.json?q=cambridge'

# Search and filter
curl 'https://rsw.me.uk/blueline/methods/search.json?q=surprise&stage=6&fields=title,url'

# Regular-expression search
curl 'https://rsw.me.uk/blueline/methods/search.json?q=%2F%5ECambridge%2F'

# Request the next page of 50 results
curl 'https://rsw.me.uk/blueline/methods/search.json?q=major&count=50&offset=50'
```

### `GET /methods/view/{url}.json`

Return one method as a one-element JSON array. The method object contains the fields listed in [Method JSON fields](#method-json-fields) and a `similar` object with groups named `differentOnlyAtLeadEnd`, `differentOnlyAtHalfLead`, `differentOnlyAtHalfLeadAndLeadEnd` and `other`. Each group contains related methods; an empty group has no matches.

For example:

```sh
curl 'https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major.json'
```

Method URL identifiers use underscores in place of spaces. Requests that canonicalise to another method URL are permanently redirected (`301`); an unknown method returns `404`.

The endpoint always returns an array, even though it contains one method.

### `GET /methods/view/{url}.png`

Render a method detail page as a PNG image.

| Parameter | Required | Type | Description |
| --- | --- | --- | --- |
| `scale` | No | Integer | Image scale; defaults to `1`. Values greater than `4` are rejected with `403`. |
| `style` | No | String | One of `numbers`, `lines`, `diagrams` or `grid`; defaults to `numbers`. |

The response has content type `image/png`; the controller does not set a `Content-Disposition` header. Missing/default style or scale values may be normalised with a permanent `301` redirect. Invalid styles return `403`, a missing method returns `404`, and image-rendering failures return `503`.

```sh
curl -L 'https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major.png' -o Cambridge_Surprise_Major.png
curl -L 'https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major.png?scale=4&style=grid' -o diagram.png
```

### `GET /methods/view.json`

Parse and display a custom method that may not be in the database. The result is a one-element array with a synthetic method object and the same `similar` group keys as a database method; each custom-method group is `null`.

| Parameter | Required | Type | Description |
| --- | --- | --- | --- |
| `notation` | Yes | String | Place notation, for example `x38x14x1258x36x14x58x16x78` or `3,1.5.1.5.1`. |
| `stage` | No | Integer | Number of bells. If omitted, the stage is guessed from the notation. |
| `title` | No | String | Optional display title. |

If the expanded notation and stage match a database method, the endpoint permanently redirects (`301`) to that method's detail URL. Missing `notation` returns `400`.

```sh
curl 'https://rsw.me.uk/blueline/methods/view.json?notation=x38x14x1258x36x14x58x16x78&stage=8&title=Example'
```

The custom method also supports `.png`; the image parameters and status behavior are the same as for a database method.

## Place notation

### `GET /services/notation.json` or `GET /services/notation.txt`

Parse place notation and return its expanded and SiRIL forms. `expanded` and `siril` are produced by distinct conversions; the former is not SiRIL notation.

| Parameter | Required | Type | Description |
| --- | --- | --- | --- |
| `notation` | Yes | String | Place notation in a supported notation style. |
| `stage` | No | Integer | Number of bells. If omitted, it is inferred from the bell symbols in the notation. |

JSON response fields are `stage`, `expanded` and `siril`. The `.txt` response contains the expanded notation on the first line and the SiRIL notation on the second. Missing `notation` returns `400`.

Example using a value checked against `PlaceNotation`:

```json
{
  "stage": 8,
  "expanded": "x38x14x1258x36x14x58x16x78x16x58x14x36x1258x14x38x12",
  "siril": "&-3-4-25-36-4-5-6-7, +2"
}
```

```sh
curl 'https://rsw.me.uk/blueline/services/notation.json?notation=x38x14x1258x36x14x58x16x78%2C12&stage=8'
curl 'https://rsw.me.uk/blueline/services/notation.txt?notation=3%2C1.5.1.5.1'
```

## oEmbed

### `GET /services/oembed.json`

The controller's intended JSON response follows oEmbed 1.0 and describes a method diagram as a photo. It accepts a `url` parameter containing a method detail-page URL on the installation's configured base URL.

| Field | Description |
| --- | --- |
| `type` | `photo`. |
| `version` | oEmbed version, `1.0`. |
| `title` | Method title. |
| `provider_name` | `Blueline`. |
| `provider_url` | The installation's home page. |
| `url` | PNG URL for the method diagram, using `scale=1&style=numbers`. |
| `width`, `height` | Dimensions read from the generated image; the controller uses `0` when a dimension is unavailable. |

The valid local response is covered by [`OembedControllerTest`](../tests/Controller/OembedControllerTest.php), which verifies these fields when the method and image routes are reachable. A prior document reported a `500` response for the endpoint, but that result could not be confirmed; production was not queried. **TODO(owner): confirm whether the historical `500` report applies to production and remains relevant.**

Only JSON is implemented; requesting XML returns `501`. The URL must match a supported method detail page. The current local integration test confirms that an invalid URL returns `500` because validation raises a generic exception; this is not a stable client error contract.

```sh
curl --get 'https://rsw.me.uk/blueline/services/oembed.json' \
  --data-urlencode 'url=https://rsw.me.uk/blueline/methods/view/Cambridge_Surprise_Major'
```

## CSV exports

### `GET /data/{table}.csv`

Stream one of the exported database tables as CSV. Responses use `text/csv` and an attachment filename. Exports are ordered by the relevant table keys.

| Table | CSV columns |
| --- | --- |
| `collections` | `id`, `name`, `description` |
| `methods` | `title`, `url`, `abbreviation`, `provisional`, `stage`, `classification`, `namemetaphone`, `notation`, `notationexpanded`, `notationsiril`, `leadheadcode`, `leadhead`, `fchgroups`, `lengthoflead`, `lengthofcourse`, `numberofleads`, `numberofhunts`, `jump`, `little`, `differential`, `plain`, `trebledodging`, `palindromic`, `doublesym`, `rotational`, `calls`, `ruleoffs`, `callingpositions`, `magic`, `cccbrid`, `methodreferences`, `extensionconstruction` |
| `methods_collections` | `id`, `position`, `method_title` |
| `methods_similar` | `method1_title`, `method2_title`, `stage`, `similarity`, `onlydifferentoverleadend` |
| `performances` | `id`, `type`, `date`, `society`, `rung_title`, `rung_url`, `reference`, `location_room`, `location_building`, `location_address`, `location_town`, `location_county`, `location_region`, `location_country`, `method_title` |

### Collection and relation fields

| Export | Field | Type | Description |
| --- | --- | --- | --- |
| `collections` | `id` | String | Collection identifier. |
| `collections` | `name` | String | Collection name. |
| `collections` | `description` | String | Collection description. |
| `methods_collections` | `id` | String | Collection identifier; aliases `collection_id`. |
| `methods_collections` | `position` | Integer | Method position within the collection. |
| `methods_collections` | `method_title` | String | Related method title. |
| `methods_similar` | `method1_title` | String | First related method title. |
| `methods_similar` | `method2_title` | String | Second related method title. |
| `methods_similar` | `stage` | Integer | Stage of the first method. |
| `methods_similar` | `similarity` | Float | Calculated similarity score; nullable in the entity. |
| `methods_similar` | `onlydifferentoverleadend` | Boolean | Whether the pair differs only over the lead end; nullable in the entity. |

### Performance fields

Performance exports use the database's lowercase column names. Except for `id` and `type`, the entity permits these fields to be null.

| Field | Type | Description |
| --- | --- | --- |
| `id` | Integer | Internal performance-record identifier. |
| `type` | String | Performance record category, such as a first tower-bell or handbell peal, or a `renamedMethod` record. |
| `date` | Date or null | Performance date, exported as a date value. |
| `society` | String or null | Society associated with the performance. |
| `rung_title` | String or null | Title used for the rung method. |
| `rung_url` | String or null | URL identifier for the rung method. |
| `reference` | String or null | Source reference. |
| `location_room` | String or null | Room or ringing space. |
| `location_building` | String or null | Building name. |
| `location_address` | String or null | Street address. |
| `location_town` | String or null | Town or locality. |
| `location_county` | String or null | County. |
| `location_region` | String or null | Region. |
| `location_country` | String or null | Country. |
| `method_title` | String | Related method title. |

In `methods_collections`, the exported `id` column is the collection identifier (`collection_id` in the database), aliased by the export query. The methods CSV uses lowercase PostgreSQL column names; the JSON method fields use camelCase. Collections and performances are available as CSV exports, not as separate JSON endpoints.

```sh
curl -O 'https://rsw.me.uk/blueline/data/methods.csv'
curl 'https://rsw.me.uk/blueline/data/collections.csv' | head -20
```

## Method JSON fields

These are the method fields exposed by the JSON serializer. Database method responses also add `similar`. A full detail response omits falsey values (`null`, `false`, `0`, `"0"`, empty strings and empty arrays) because of the serializer's filtering; field-selected search results include each requested known field, including a `null` value.

| Field | Type | Description |
| --- | --- | --- |
| `title` | String | Method title. |
| `url` | String | Canonical URL identifier. |
| `abbreviation` | String or null | Method abbreviation. |
| `provisional` | Boolean or null | Provisional status. |
| `stage` | Integer | Number of bells. |
| `classification` | String or null | Method classification. |
| `nameMetaphone` | String or null | Phonetic key used for search matching. |
| `notation` | String | Source place notation, which may use abbreviations. |
| `notationExpanded` | String | Expanded and normalised place notation. |
| `notationSiril` | String or null | SiRIL representation derived from expanded notation and stage when needed. |
| `leadHeadCode` | String or null | Lead-head code, when defined. |
| `leadHead` | String | Lead-head change. |
| `fchGroups` | String or null | FCH group value from the source data. |
| `lengthOfLead` | Integer | Number of changes in a lead. |
| `lengthOfCourse` | Integer | Number of changes in a course. |
| `numberOfLeads` | Integer or null | Number of leads in a course, when supplied. |
| `numberOfHunts` | Integer or null | Number of hunting bells, when supplied. |
| `jump` | Boolean or null | Source flag indicating a jump method. |
| `little` | Boolean or null | Source flag indicating a little method. |
| `differential` | Boolean or null | Source flag indicating a differential method. |
| `plain` | Boolean or null | Source plain-method flag. |
| `trebleDodging` | Boolean or null | Source treble-dodging flag. |
| `palindromic` | Boolean or null | Whether the method is palindromic. |
| `doubleSym` | Boolean or null | Source flag for double symmetry. |
| `rotational` | Boolean or null | Source flag for rotational symmetry. |
| `calls` | Array or null | Call definitions used for method displays. |
| `ruleOffs` | Array or null | Lead-display divisions. |
| `callingPositions` | Array or null | Calling-position definitions. |
| `magic` | Integer or null | Search-ranking value. |
| `cccbrId` | String or null | CCCBR method identifier. |
| `methodReferences` | String or null | Method references and source notes. |
| `extensionConstruction` | String or null | Notes about construction at higher stages. |

The serializer is implemented by [`Method::__toArray()`](../src/Entity/Method.php); derived-field and source details are in [Data and copyright](data-and-copyright.md).

## Status codes and redirects

| Status | Documented behavior |
| --- | --- |
| `200` | Successful response. |
| `301` | Permanent canonicalisation redirect for method URLs, PNG defaults, or custom notation matching a database method. |
| `400` | Required notation parameter missing, or invalid search regular expression. |
| `403` | PNG request uses a style outside the supported list or a scale greater than `4`. |
| `404` | Unknown method or route/table not supported by the route definition. |
| `501` | oEmbed XML response requested; XML is not implemented. |
| `503` | PNG rendering is temporarily unavailable. |

Unexpected errors may use Symfony's default error response; no stable JSON error-body schema is promised.

## Caching and client usage

The application marks these responses public and sets the following maximum ages:

| Endpoint | `Cache-Control` max age | Conditional update |
| --- | ---: | --- |
| Method search | 6 hours | `Last-Modified` uses the database update timestamp. |
| Method detail and custom method view | 36 hours | `Last-Modified` uses the database update timestamp. |
| Place-notation conversion | 36 hours | No database update timestamp is set. |
| oEmbed | 36 hours | No database update timestamp is set. |
| CSV exports | 7 days | `Last-Modified` uses the database update timestamp. |

Where a `Last-Modified` header is present, clients can send `If-Modified-Since` and handle a conditional response. CDN behavior may add another cache layer.

There is no active application-level `cache_bust` parameter handling. Do not rely on that parameter to invalidate responses. **TODO(owner): confirm whether the production CDN treats arbitrary query parameters as cache-key changes before documenting a cache-bypass policy.**

Recommended client behavior:

- Cache responses for the advertised period when practical.
- Use conditional requests when a `Last-Modified` header is present.
- Avoid bursts of parallel requests, especially for PNG images.
- Treat the public endpoint as a shared service that may be rate-limited.

## Related documentation

- [Data sources and derived fields](data-and-copyright.md)
- [Development and installation](development-deployment.md)
- [Production deployment](production-deployment.md)
- [Documentation index](README.md)
