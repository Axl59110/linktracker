# STORY-008: URL Validator Service - SSRF Protection

**Epic:** Sprint 1 - Core Infrastructure
**Points:** 5
**Status:** Completed ✅
**Date:** 2026-02-12

## Objectif

Créer un service de validation d'URLs pour prévenir les attaques SSRF (Server-Side Request Forgery).

## Raison d'Être - CRITIQUE SÉCURITÉ

L'application va faire des requêtes HTTP vers des URLs fournies par les utilisateurs (backlinks à monitorer). **Sans protection SSRF**, un attaquant pourrait :

1. **Accéder au réseau interne**
   - Localhost (127.0.0.1)
   - Réseaux privés (192.168.x.x, 10.x.x.x)
   - Services internes non protégés

2. **Scanner le réseau local**
   - Découvrir les services internes
   - Identifier les ports ouverts

3. **Exploiter des services internes**
   - Accéder à des APIs internes
   - Lire des métadonnées cloud (169.254.169.254)

4. **DNS Rebinding**
   - Domaine qui résout vers IP privée
   - Contournement de blacklist naïve

## Architecture de la Solution

### 1. Service UrlValidator

**Fichier:** `app/Services/Security/UrlValidator.php`

Le service valide les URLs en plusieurs étapes :

```php
class UrlValidator
{
    private const BLOCKED_IP_RANGES = [
        '127.0.0.0/8',      // Localhost
        '10.0.0.0/8',       // RFC1918 private network
        '172.16.0.0/12',    // RFC1918 private network
        '192.168.0.0/16',   // RFC1918 private network
        '169.254.0.0/16',   // Link-local
        '0.0.0.0/8',        // Current network
        '224.0.0.0/4',      // Multicast
        '240.0.0.0/4',      // Reserved
    ];

    private const ALLOWED_PROTOCOLS = ['http', 'https'];

    public function validate(string $url): void
    {
        // 1. Parse l'URL
        // 2. Vérifier le protocole (http/https uniquement)
        // 3. Résoudre le DNS pour obtenir l'IP réelle
        // 4. Vérifier si l'IP est dans une plage bloquée
        // 5. Lance SsrfException si bloqué
    }

    private function ipInRange(string $ip, string $range): bool
    {
        // Vérifie si une IP appartient à une plage CIDR
    }
}
```

### 2. Exception Personnalisée

**Fichier:** `app/Exceptions/SsrfException.php`

```php
class SsrfException extends Exception
{
    // Exception levée quand une URL est bloquée pour des raisons de sécurité
}
```

### 3. Form Requests avec Validation

**Fichiers:**
- `app/Http/Requests/StoreProjectRequest.php`
- `app/Http/Requests/UpdateProjectRequest.php`

Intégration de la validation SSRF dans les FormRequests :

```php
'url' => [
    'required',
    'url',
    'max:2048',
    function ($attribute, $value, $fail) {
        try {
            app(UrlValidator::class)->validate($value);
        } catch (SsrfException $e) {
            $fail("L'URL est bloquée pour des raisons de sécurité : " . $e->getMessage());
        }
    },
]
```

## Flux de Validation

```
User Input URL
    ↓
Parse URL (parse_url)
    ↓
Check Protocol ✓
(http/https only)
    ↓
Resolve DNS ✓
(gethostbyname)
    ↓
Check IP Range ✓
(not in blocked ranges)
    ↓
URL Valid ✅
```

## Plages IP Bloquées

| Plage CIDR | Description | Raison |
|------------|-------------|--------|
| `127.0.0.0/8` | Localhost | Accès serveur local |
| `10.0.0.0/8` | Réseau privé classe A | Réseau interne |
| `172.16.0.0/12` | Réseau privé classe B | Réseau interne |
| `192.168.0.0/16` | Réseau privé classe C | Réseau local |
| `169.254.0.0/16` | Link-local | Métadonnées cloud |
| `0.0.0.0/8` | Current network | Non routable |
| `224.0.0.0/4` | Multicast | Non applicable |
| `240.0.0.0/4` | Réservé | Futur usage |

## Tests Complets

**Fichier:** `tests/Unit/Services/Security/UrlValidatorTest.php`

**18 tests / 21 assertions - 100% passés ✅**

### Tests de Blocage

1. ✅ Bloque localhost (127.0.0.1)
2. ✅ Bloque localhost (hostname "localhost")
3. ✅ Bloque réseau privé 10.x.x.x
4. ✅ Bloque réseau privé 192.168.x.x
5. ✅ Bloque réseau privé 172.16-31.x.x
6. ✅ Bloque link-local 169.254.x.x (métadonnées cloud)
7. ✅ Bloque 0.0.0.0
8. ✅ Bloque multicast 224.0.0.0/4

### Tests d'Autorisation

9. ✅ Autorise domaine public (example.com)
10. ✅ Autorise domaine public (google.com)
11. ✅ Autorise IP publique directe (8.8.8.8)
12. ✅ Autorise sous-domaine public (www.google.com)

### Tests de Protocole

13. ✅ Accepte HTTPS
14. ✅ Accepte HTTP
15. ✅ Rejette FTP
16. ✅ Rejette file://

### Tests de Format

17. ✅ Rejette URL sans protocole
18. ✅ Rejette URL malformée

```bash
# Exécuter les tests
php artisan test tests/Unit/Services/Security/UrlValidatorTest.php

# Résultat
PASS  Tests\Unit\Services\Security\UrlValidatorTest
✓ blocks localhost 127 0 0 1
✓ blocks localhost hostname
✓ blocks private network 10
✓ blocks private network 192 168
✓ blocks private network 172 16
✓ blocks link local 169 254
✓ allows public domain example com
✓ allows public domain google
✓ rejects ftp protocol
✓ rejects file protocol
✓ accepts https protocol
✓ accepts http protocol
✓ rejects url without protocol
✓ rejects malformed url
✓ blocks 0 0 0 0
✓ blocks multicast
✓ allows public ip
✓ allows public subdomain

Tests:    18 passed (21 assertions)
Duration: 1.82s
```

## Utilisation

### Dans les Controllers

```php
use App\Http\Requests\StoreProjectRequest;

class ProjectController extends Controller
{
    public function store(StoreProjectRequest $request)
    {
        // La validation SSRF est automatique
        // Si URL invalide, erreur 422 avec message clair

        $project = auth()->user()->projects()->create([
            'name' => $request->name,
            'url' => $request->url, // ✅ Validée contre SSRF
        ]);

        return response()->json($project, 201);
    }
}
```

### Utilisation Standalone

```php
use App\Services\Security\UrlValidator;
use App\Exceptions\SsrfException;

$validator = app(UrlValidator::class);

try {
    $validator->validate('https://example.com');
    // ✅ URL valide
} catch (SsrfException $e) {
    // ❌ URL bloquée
    echo $e->getMessage();
}
```

## Exemples de Protection

### ✅ URLs Autorisées

```php
'https://example.com'           // Domaine public
'https://www.google.com/search' // Sous-domaine public
'http://8.8.8.8'                // IP publique (Google DNS)
'https://api.github.com'        // API publique
```

### ❌ URLs Bloquées

```php
'http://127.0.0.1/admin'              // Localhost
'http://localhost:8000'               // Localhost hostname
'http://192.168.1.1/router'           // Réseau privé
'http://10.0.0.5/internal'            // Réseau privé
'http://172.16.0.1/service'           // Réseau privé
'http://169.254.169.254/metadata'     // AWS/GCP metadata
'ftp://example.com/file'              // Protocole non-HTTP
'file:///etc/passwd'                  // File system
```

## Messages d'Erreur

Les messages sont clairs et aident à comprendre le problème :

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "url": [
      "L'URL est bloquée pour des raisons de sécurité : Accès à 127.0.0.1 (localhost) bloqué pour des raisons de sécurité (protection SSRF)"
    ]
  }
}
```

## Critères d'Acceptation

- [x] UrlValidator bloque localhost (127.0.0.1)
- [x] UrlValidator bloque réseaux privés (10.x, 192.168.x, 172.16-31.x)
- [x] UrlValidator bloque link-local (169.254.x.x)
- [x] UrlValidator autorise domaines publics
- [x] Exception SsrfException lancée correctement
- [x] 18 tests unitaires passent (21 assertions)
- [x] Validation intégrée dans FormRequests
- [x] Messages d'erreur clairs

## Points Complétés

**5 points** - Story complétée avec succès.

## Commit

```
feat(security): implement SSRF protection with UrlValidator (STORY-008)

Implémentation complète de la protection SSRF pour valider les URLs:

Sécurité:
- Validation de protocole (http/https uniquement)
- Résolution DNS pour obtenir l'IP réelle
- Blocage des plages IP privées (RFC1918)
- Blocage localhost, link-local, multicast
- Protection contre les attaques DNS rebinding

Fichiers ajoutés:
- app/Services/Security/UrlValidator.php
- app/Exceptions/SsrfException.php
- app/Http/Requests/StoreProjectRequest.php
- app/Http/Requests/UpdateProjectRequest.php
- tests/Unit/Services/Security/UrlValidatorTest.php

Tests: 18/18 passés (21 assertions)

STORY-008 completed - 5 points
```

## Points d'Attention

### Sécurité

1. **Résolution DNS** : On résout le DNS AVANT de vérifier l'IP pour éviter le DNS rebinding
2. **Double vérification** : On vérifie l'IP résolue ET l'hostname s'il est une IP directe
3. **CIDR matching** : Utilisation de masques binaires pour couvrir toutes les plages

### Limitations Connues

1. **DNS Rebinding Avancé** : Un attaquant pourrait utiliser un TTL très court pour changer l'IP entre la validation et la requête
   - **Mitigation** : Valider juste avant chaque requête HTTP

2. **IPv6** : Actuellement seul IPv4 est supporté
   - **TODO futur** : Ajouter support IPv6 avec plages `::1`, `fc00::/7`, `fe80::/10`

3. **Redirections HTTP** : Une URL valide pourrait rediriger vers une URL privée
   - **Mitigation** : Désactiver les redirections dans le client HTTP

## Prochaines Étapes

Cette validation sera utilisée dans :
- **Sprint 2:** Jobs de scraping de backlinks
- **Sprint 2:** Vérification HTTP des URLs
- Tous les endpoints acceptant des URLs utilisateur

## Références

- [OWASP SSRF](https://owasp.org/www-community/attacks/Server_Side_Request_Forgery)
- [RFC1918 Private Networks](https://datatracker.ietf.org/doc/html/rfc1918)
- [Cloud Metadata Endpoints](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/ec2-instance-metadata.html)
