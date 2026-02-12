# STORY-013: HTTP Service for Checking Backlinks

**Epic:** EPIC-002 - Backlinks Management
**Points:** 5
**Status:** ✅ Completed
**Branch:** `feature/STORY-013-http-backlink-checker`

## Objectif

Créer un service HTTP robuste pour vérifier la présence physique d'un backlink sur une page source, avec protection SSRF, détection des attributs rel, et gestion complète des erreurs.

## Description

Cette story implémente le cœur du système de monitoring des backlinks :
- Requête HTTP vers la page source avec timeout configuré
- Parsing HTML pour trouver le lien vers target_url
- Extraction des informations : anchor text, attributs rel (nofollow, sponsored, ugc)
- Détermination si le lien est dofollow ou nofollow
- Protection SSRF intégrée (réutilise UrlValidator)
- Normalisation d'URLs pour gérer les variations (http/https, www, trailing slash)
- Gestion des erreurs réseau, HTTP, et timeouts
- Tests complets (15 tests / 41 assertions)

## Implémentation

### 1. BacklinkCheckerService

**Fichier:** `app/Services/Backlink/BacklinkCheckerService.php`

Service principal pour vérifier les backlinks.

#### Constructeur et Configuration

```php
protected UrlValidator $urlValidator;
protected int $timeout = 30; // 30 secondes par défaut
protected string $userAgent = 'LinkTracker-Bot/1.0 (Backlink Monitoring)';

public function __construct(UrlValidator $urlValidator)
{
    $this->urlValidator = $urlValidator;
}
```

**Méthodes de configuration:**
- `setTimeout(int $timeout): self` - Configure le timeout HTTP
- `setUserAgent(string $userAgent): self` - Configure le User-Agent

#### Méthode Principale : check()

```php
public function check(Backlink $backlink): array
```

**Retourne un array avec:**
```php
[
    'is_present' => bool,           // true si le backlink est trouvé
    'http_status' => int|null,      // Code HTTP (200, 404, 500, etc.)
    'anchor_text' => string|null,   // Texte du lien (ex: "cliquez ici")
    'rel_attributes' => string|null, // Attributs rel séparés par virgules (ex: "nofollow,sponsored")
    'is_dofollow' => bool,          // true si pas de rel="nofollow"
    'error_message' => string|null, // Message d'erreur si échec
]
```

#### Workflow de Vérification

**1. Validation SSRF**
```php
$this->urlValidator->validate($backlink->source_url);
```
Lance `SsrfException` si l'URL est bloquée (localhost, réseaux privés).

**2. Requête HTTP**
```php
$response = Http::timeout($this->timeout)
    ->withUserAgent($this->userAgent)
    ->get($backlink->source_url);
```

**3. Vérification du Code HTTP**
```php
if (!$response->successful()) {
    $result['error_message'] = "HTTP {$response->status()} - Page non accessible";
    return $result;
}
```

**4. Parsing HTML**
```php
$html = $response->body();
$linkData = $this->findLinkInHtml($html, $backlink->target_url);
```

### 2. Méthode findLinkInHtml()

Parse le HTML avec DOMDocument et DOMXPath pour trouver le lien.

#### Processus

**1. Initialisation DOM**
```php
libxml_use_internal_errors(true); // Ignore les erreurs HTML
$dom = new DOMDocument();
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
$dom->loadHTML($html);
```

**2. Recherche des Liens**
```php
$xpath = new DOMXPath($dom);
$links = $xpath->query('//a[@href]'); // Tous les <a> avec attribut href
```

**3. Comparaison d'URLs**
```php
foreach ($links as $link) {
    $href = $link->getAttribute('href');
    if ($this->urlsMatch($href, $targetUrl)) {
        // Lien trouvé !
    }
}
```

**4. Extraction des Données**
```php
$anchorText = trim($link->textContent);
$rel = $link->getAttribute('rel');
$relAttributes = $rel ? array_map('trim', explode(' ', strtolower($rel))) : [];
$isDofollow = !in_array('nofollow', $relAttributes);

return [
    'anchor_text' => $anchorText ?: null,
    'rel_attributes' => !empty($relAttributes) ? implode(',', $relAttributes) : null,
    'is_dofollow' => $isDofollow,
];
```

### 3. Normalisation d'URLs

#### Méthode urlsMatch()

Compare deux URLs en gérant les variations courantes.

**Étapes:**
1. Normaliser les deux URLs
2. Retirer les protocoles (http:// et https://)
3. Comparer sans protocoles
4. Si différent, retirer "www." et comparer à nouveau

```php
protected function urlsMatch(string $url1, string $url2): bool
{
    $normalized1 = $this->normalizeUrl($url1);
    $normalized2 = $this->normalizeUrl($url2);

    // Retirer http:// ou https://
    $normalized1WithoutProtocol = preg_replace('/^https?:\/\//', '', $normalized1);
    $normalized2WithoutProtocol = preg_replace('/^https?:\/\//', '', $normalized2);

    if ($normalized1WithoutProtocol === $normalized2WithoutProtocol) {
        return true;
    }

    // Retirer www.
    $normalized1WithoutWww = preg_replace('/^www\./', '', $normalized1WithoutProtocol);
    $normalized2WithoutWww = preg_replace('/^www\./', '', $normalized2WithoutProtocol);

    return $normalized1WithoutWww === $normalized2WithoutWww;
}
```

#### Méthode normalizeUrl()

Normalise une URL pour la comparaison.

**Transformations:**
- Convertit en minuscules
- Parse avec `parse_url()`
- Reconstruit : `{scheme}://{host}{path}`
- Retire trailing slash (sauf pour racine `/`)
- Ajoute query string si présente

**Exemples:**
```
https://MySite.com/Page/ → https://mysite.com/page
http://www.example.com → http://www.example.com/
https://site.com/page?id=1 → https://site.com/page?id=1
```

### 4. Gestion des Erreurs

#### SSRF Exception

```php
catch (SsrfException $e) {
    $result['error_message'] = 'SSRF Protection: ' . $e->getMessage();
    Log::warning('SSRF attempt blocked during backlink check', [
        'backlink_id' => $backlink->id,
        'source_url' => $backlink->source_url,
    ]);
}
```

Bloque les URLs dangereuses avant même de faire la requête HTTP.

#### Exceptions Générales

```php
catch (\Exception $e) {
    $result['error_message'] = 'Erreur: ' . $e->getMessage();
    Log::error('Backlink check failed', [
        'backlink_id' => $backlink->id,
        'error' => $e->getMessage(),
    ]);
}
```

Capture toutes les autres erreurs (timeout, DNS, réseau, parsing).

## Tests

### Fichier de Tests

**Fichier:** `tests/Unit/Services/Backlink/BacklinkCheckerServiceTest.php`

**15 tests / 41 assertions**

### Scénarios Testés

#### 1. Détection de Backlinks

**✓ can_detect_present_backlink_with_dofollow**
```php
$html = '<a href="https://mysite.com">this great site</a>';
$result = $this->service->check($backlink);

$this->assertTrue($result['is_present']);
$this->assertEquals('this great site', $result['anchor_text']);
$this->assertNull($result['rel_attributes']);
$this->assertTrue($result['is_dofollow']);
```

**✓ can_detect_present_backlink_with_nofollow**
```php
$html = '<a href="https://mysite.com" rel="nofollow sponsored">Visit here</a>';
$result = $this->service->check($backlink);

$this->assertEquals('nofollow,sponsored', $result['rel_attributes']);
$this->assertFalse($result['is_dofollow']);
```

**✓ can_detect_ugc_link**
```php
$html = '<a href="https://mysite.com" rel="ugc nofollow">User link</a>';
$result = $this->service->check($backlink);

$this->assertStringContainsString('ugc', $result['rel_attributes']);
```

**✓ detects_missing_backlink**
```php
$html = '<p>No backlink here</p>';
$result = $this->service->check($backlink);

$this->assertFalse($result['is_present']);
$this->assertEquals('Backlink non trouvé dans la page', $result['error_message']);
```

#### 2. Gestion des Erreurs HTTP

**✓ handles_http_404_error**
```php
Http::fake(['example.com/*' => Http::response('Not Found', 404)]);
$result = $this->service->check($backlink);

$this->assertFalse($result['is_present']);
$this->assertEquals(404, $result['http_status']);
$this->assertStringContainsString('404', $result['error_message']);
```

**✓ handles_http_500_error**
```php
Http::fake(['example.com/*' => Http::response('Server Error', 500)]);
$result = $this->service->check($backlink);

$this->assertEquals(500, $result['http_status']);
```

#### 3. Protection SSRF

**✓ blocks_ssrf_attempt_localhost**
```php
$backlink->source_url = 'http://localhost/admin';
$result = $this->service->check($backlink);

$this->assertFalse($result['is_present']);
$this->assertStringContainsString('SSRF', $result['error_message']);
Http::assertNothingSent(); // Aucune requête HTTP effectuée
```

**✓ blocks_ssrf_attempt_private_network**
```php
$backlink->source_url = 'http://192.168.1.1/admin';
$result = $this->service->check($backlink);

$this->assertStringContainsString('SSRF', $result['error_message']);
```

#### 4. Normalisation d'URLs

**✓ normalizes_urls_with_trailing_slash**
```php
// target_url: https://mysite.com/page
// HTML: <a href="https://mysite.com/page/">Link</a>
$result = $this->service->check($backlink);

$this->assertTrue($result['is_present']); // Match réussi
```

**✓ normalizes_urls_with_http_vs_https**
```php
// target_url: https://mysite.com
// HTML: <a href="http://mysite.com">Link</a>
$result = $this->service->check($backlink);

$this->assertTrue($result['is_present']); // Match malgré protocole différent
```

**✓ normalizes_urls_with_www**
```php
// target_url: https://mysite.com
// HTML: <a href="https://www.mysite.com">Link</a>
$result = $this->service->check($backlink);

$this->assertTrue($result['is_present']); // Match malgré www
```

#### 5. Cas Particuliers

**✓ handles_empty_anchor_text**
```php
$html = '<a href="https://mysite.com"></a>'; // Pas de texte
$result = $this->service->check($backlink);

$this->assertTrue($result['is_present']);
$this->assertNull($result['anchor_text']);
```

**✓ handles_multiple_links_to_same_target**
```php
$html = '
    <a href="https://mysite.com" rel="nofollow">First link</a>
    <a href="https://mysite.com">Second link</a>
';
$result = $this->service->check($backlink);

// Retourne le premier lien trouvé
$this->assertEquals('First link', $result['anchor_text']);
```

**✓ can_configure_timeout**
```php
$this->service->setTimeout(60);
$result = $this->service->check($backlink);
$this->assertTrue($result['is_present']);
```

**✓ can_configure_user_agent**
```php
$this->service->setUserAgent('CustomBot/1.0');
$result = $this->service->check($backlink);
$this->assertTrue($result['is_present']);
```

## Résultats des Tests

```bash
php artisan test tests/Unit/Services/Backlink/BacklinkCheckerServiceTest.php

✓ can detect present backlink with dofollow (0.44s)
✓ can detect present backlink with nofollow (0.04s)
✓ can detect ugc link (0.04s)
✓ detects missing backlink (0.04s)
✓ handles http 404 error (0.04s)
✓ handles http 500 error (0.04s)
✓ blocks ssrf attempt localhost (0.05s)
✓ blocks ssrf attempt private network (0.05s)
✓ normalizes urls with trailing slash (0.04s)
✓ normalizes urls with http vs https (0.04s)
✓ normalizes urls with www (0.05s)
✓ handles empty anchor text (0.04s)
✓ handles multiple links to same target (0.04s)
✓ can configure timeout (0.04s)
✓ can configure user agent (0.04s)

Tests:    15 passed (41 assertions)
Duration: 1.22s
```

**Tous les tests du projet:**
```bash
php artisan test

Tests:    97 passed (275 assertions)
Duration: 5.43s
```

## Fichiers Créés/Modifiés

**Créés:**
- `app/Services/Backlink/BacklinkCheckerService.php` - Service principal
- `tests/Unit/Services/Backlink/BacklinkCheckerServiceTest.php` - Tests unitaires
- `docs/stories/STORY-013.md` - Documentation

**Modifiés:**
- Aucun (service standalone)

## Points d'Attention

### Protection SSRF Intégrée

Le service réutilise `UrlValidator` existant (STORY-008) pour bloquer les URLs dangereuses **avant** de faire la requête HTTP. C'est une double protection :
- Validation lors de la création du backlink (FormRequest)
- Validation lors de la vérification (BacklinkCheckerService)

### Parsing HTML Robuste

Utilisation de `libxml_use_internal_errors(true)` pour gérer les erreurs HTML mal formés sans crasher le script.

```php
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();
```

### Encodage UTF-8

Conversion de l'encodage avant parsing pour gérer les caractères spéciaux :
```php
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
```

### Timeout Configuré

Timeout par défaut de 30 secondes pour éviter que les requêtes HTTP bloquent indéfiniment.

```php
protected int $timeout = 30;
```

Peut être modifié avec `setTimeout()`.

### User-Agent Identifiable

User-Agent personnalisé pour identifier le bot dans les logs serveurs :
```
LinkTracker-Bot/1.0 (Backlink Monitoring)
```

### Normalisation Flexible

La normalisation d'URLs est intentionnellement flexible pour gérer :
- http vs https (considérés identiques)
- www vs sans www (considérés identiques)
- Trailing slash (ignoré)
- Query strings (conservées)

Cela évite les faux négatifs causés par des variations mineures d'URL.

### Logging

Les erreurs sont loggées avec contexte :
- SSRF attempts → Warning
- Check failures → Error

Facilite le debugging et le monitoring en production.

## Utilisation

### Exemple Basique

```php
use App\Services\Backlink\BacklinkCheckerService;
use App\Models\Backlink;

$service = app(BacklinkCheckerService::class);

$backlink = Backlink::find(1);
$result = $service->check($backlink);

if ($result['is_present']) {
    echo "Backlink trouvé !";
    echo "Anchor text: " . $result['anchor_text'];
    echo "Dofollow: " . ($result['is_dofollow'] ? 'Oui' : 'Non');
} else {
    echo "Erreur: " . $result['error_message'];
}
```

### Avec Configuration

```php
$service = app(BacklinkCheckerService::class);
$service->setTimeout(60)
        ->setUserAgent('MyCustomBot/2.0');

$result = $service->check($backlink);
```

### Dans un Job (Story-014)

Le service sera utilisé dans `CheckBacklinkJob` pour les vérifications asynchrones :

```php
class CheckBacklinkJob implements ShouldQueue
{
    public function handle(BacklinkCheckerService $service)
    {
        $result = $service->check($this->backlink);

        // Créer un BacklinkCheck avec les résultats
        $this->backlink->checks()->create([
            'is_present' => $result['is_present'],
            'http_status' => $result['http_status'],
            // ...
        ]);
    }
}
```

## Prochaines Étapes

- ⏳ **STORY-014: Check Backlink Job** (5 points)
  - Créer le job Laravel pour vérifications asynchrones
  - Utiliser BacklinkCheckerService
  - Créer des BacklinkCheck records
  - Mettre à jour le status du Backlink

- ⏳ **STORY-017: Schedule Backlink Checks** (7 points)
  - Planifier vérifications automatiques (quotidien, hebdomadaire)
  - Command Laravel pour dispatcher les jobs
  - Configuration dans Task Scheduler

## Commits

```bash
git add .
git commit -m "feat(backlinks): implement HTTP Service for Checking Backlinks (STORY-013)" -m "- BacklinkCheckerService with HTTP requests and HTML parsing" -m "- SSRF protection integrated (reuses UrlValidator)" -m "- URL normalization (http/https, www, trailing slash)" -m "- Detection of anchor text, rel attributes, dofollow/nofollow" -m "- Comprehensive error handling (HTTP errors, SSRF, network failures)" -m "- Configurable timeout and User-Agent" -m "- 15 unit tests with 41 assertions (all passing)" -m "- Total: 97 tests passing (275 assertions)" -m "" -m "Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```
