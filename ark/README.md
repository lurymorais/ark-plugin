# ARK Plugin for OJS 3.5.x

**Archival Resource Key (ARK) plugin for Open Journal Systems 3.5.x**

## Features

- Generate ARK identifiers only for **Articles**
- Format: `ark:NAAN/CUSTOMxxxx-yyyy` (e.g., `ark/CRL5432-ABCD`)
- Configurable ARK prefix (2-40 chars)
- Configurable custom suffix prefix (2-6 uppercase letters)
- Automatic duplicate detection and prevention
- **Built-in resolver** (works without editing main .htaccess)
- Ready for NAAN registration (n2t.net)

## Installation

1. Copy the `ark` folder to `plugins/pubIds/`
2. Go to **Settings > Website > Plugins**
3. Find "ARK PubId Plugin" and enable it
4. Configure the plugin:
   - **Enable ARK for Articles** (must be checked)
   - **ARK Prefix**: The fixed prefix (e.g., `ark:16081`)
   - **Custom Suffix Prefix**: The prefix before the random part (2-6 letters, e.g., `MIT`, `UERN`, `CRL`)
   - **Resolver URL**: `https://n2t.net/` (or your own resolver)

## Configuration Example

| Setting | Value |
|---------|-------|
| Enable ARK for Articles | ✅ Checked |
| ARK Prefix | `ark:16081` |
| Custom Suffix Prefix | `CRL` |
| Resolver URL | `https://n2t.net/` |

Resulting ARK: `ark:16081/CRL6522-QVWX`

## NAAN Registration (n2t.net)

After installing the plugin, configure your NAAN target to:
https://yourjournal.com/plugins/pubIds/ark/resolver.php?ark=$%7Bvalue%7D

========================
Example for NAAN 16081:
https://revistacarnaubais.com.br/plugins/pubIds/ark/resolver.php?ark=$%7Bvalue%7D


## Usage

- Click **"Gerar ARK"** button in the Identifiers section of the article form
- Duplicate or invalid ARKs are prevented automatically

## Resolver Integration

The plugin includes a built-in resolver that works without editing your main `.htaccess` file. The resolver:

1. Receives the ARK suffix via `?ark=` parameter
2. Queries the database to find the corresponding article
3. Redirects to the article page (302 Found)

**Direct access example:**
https://yourjournal.com/plugins/pubIds/ark/resolver.php?ark=CRL2244-AABB


## Requirements

- OJS 3.5.x
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+

## Troubleshooting

### Resolver returns 403 Forbidden
The plugin includes a `.htaccess` file that grants access to `resolver.php`. If you still get 403, check if your main `.htaccess` has conflicting rules.

### ARK not appearing in article form
Ensure "Enable ARK for Articles" is checked in plugin settings.

### Duplicate ARK error
The plugin automatically prevents duplicates. If you see this error, click "Gerar ARK" to generate a new unique identifier.

## Uninstallation

1. Disable the plugin in **Settings > Website > Plugins**
2. Remove the `ark` folder from `plugins/pubIds/`
3. (Optional) Remove ARK data from database:
   ```sql
   DELETE FROM publication_settings WHERE setting_name = 'pub-id::ark';

License
GNU General Public License v2 - See LICENSE file for details.

Author
Lury Morais (2026)

Credits
Based on original pkp-ark-pubid plugin by Yasiel Pérez Vera (2021)


Built for Carnaubais Revista de Literatura

