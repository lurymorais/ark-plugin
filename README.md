# 🌐 ARK Plugin for OJS 3.5.x

> **Choose your language / Escolha seu idioma / Elija su idioma:**  
> [🇺🇸 English](#english) | [🇧🇷 Português](#português) | [🇪🇸 Español](#español)

---

<a name="english"></a>
## 🇺🇸 English

**Archival Resource Key (ARK) plugin for Open Journal Systems 3.5.x**

### Features

- Generate ARK identifiers only for Articles
- Format: ark:NAAN/CUSTOMxxxx-yyyy (e.g., ark:16081/CRL5432-ABCD)
- Configurable custom suffix prefix (2-6 uppercase letters)
- Automatic duplicate detection and prevention
- Built-in resolver (works without editing main .htaccess)
- Ready for NAAN registration (n2t.net)

### Installation

IMPORTANT: When you download the plugin from GitHub, the folder may be named ark-plugin-main or ark-plugin-2.0.0. You MUST rename it to ark before copying to your OJS installation.

1. Download the plugin from GitHub
2. Rename the folder to ark (if not already named ark)
3. Copy the ark folder to plugins/pubIds/
4. Go to Settings > Website > Plugins
5. Find "ARK" Public Identifier Plugins and enable it
6. Configure the plugin:
   - Enable ARK for Articles (must be checked)
   - ARK Prefix: The fixed prefix (e.g., ark:16081)
   - Custom Suffix Prefix: The prefix before the random part (2-6 letters, e.g., MIT, UERN, CRL)
   - Resolver URL: https://n2t.net/ (or your own resolver)

### Configuration Example

Enable ARK for Articles: Checked
ARK Prefix: ark:16081
Custom Suffix Prefix: CRL
Resolver URL: https://n2t.net/

Resulting ARK: https://n2t.net/ark:16081/CRL6522-QVWX

### NAAN Registration (n2t.net)

After installing the plugin, configure your NAAN target to:

https://yourjournal.com/plugins/pubIds/ark/resolver.php?ark=${value}

Example:
https://revistacarnaubais.com.br/plugins/pubIds/ark/resolver.php?ark=${value}

### Usage

- Click "Generate ARK" button in the Identifiers section of the article form
- Duplicate or invalid ARKs are prevented automatically

### Resolver Integration

The plugin includes a built-in resolver that works without editing your main .htaccess file. The resolver:

1. Receives the ARK suffix via ?ark= parameter
2. Queries the database to find the corresponding article
3. Redirects to the article page (302 Found)

Direct access example:
https://yourjournal.com/plugins/pubIds/ark/resolver.php?ark=CRL2244-AABB

### Requirements

- OJS 3.5.x
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+

### Troubleshooting

Resolver returns 403 Forbidden
The plugin includes a .htaccess file that grants access to resolver.php. If you still get 403, check if your main .htaccess has conflicting rules.

ARK not appearing in article form
Ensure "Enable ARK for Articles" is checked in plugin settings.

Duplicate ARK error
The plugin automatically prevents duplicates. If you see this error, click "Generate ARK" to generate a new unique identifier.

### Uninstallation

1. Disable the plugin in Settings > Website > Plugins
2. Remove the ark folder from plugins/pubIds/
3. (Optional) Remove ARK data from database using SQL:
   DELETE FROM publication_settings WHERE setting_name = 'pub-id::ark';

### License

GNU General Public License v2 - See LICENSE file for details.

### Author

Lury Morais (2026)

### Credits

Based on original pkp-ark-pubid plugin by Yasiel Pérez Vera (2021)

---

[⬆ Back to top](#english)

---

<a name="português"></a>
## 🇧🇷 Português

**Plugin ARK para OJS 3.5.x**

### Funcionalidades

- Gera identificadores ARK apenas para Artigos
- Formato: ark:NAAN/SUASIGLAxxxx-yyyy (ex.: ark:16081/CRL5432-ABCD)
- Prefixo personalizado (2-6 letras maiúsculas)
- Detecção e prevenção automática de duplicatas
- Resolvedor integrado (funciona sem editar o .htaccess principal)
- Pronto para registro NAAN (n2t.net)

### Instalação

IMPORTANTE: Quando você baixar o plugin do GitHub, a pasta pode estar nomeada como ark-plugin-main ou ark-plugin-2.0.0. Você DEVE renomeá-la para "ark" antes de copiar para sua instalação do OJS.

1. Baixe o plugin do GitHub
2. Renomeie a pasta para "ark" (se já não estiver nomeada como ark)
3. Copie a pasta ark para plugins/pubIds/
4. Vá para Configurações > Website > Plugins
5. Encontre o plugin de identificador público "ARK" e ative-o
6. Configure o plugin:
   - Habilitar ARK para Artigos (deve estar marcado)
   - Prefixo ARK: O seu prefixo NAAN (ex.: ark:16081)
   - Prefixo Personalizado: O prefixo antes da parte aleatória (2-6 letras, ex.: MIT, UERN, CRL)
   - URL do Resolvedor: https://n2t.net/ (ou seu próprio resolvedor)

### Exemplo de Configuração

Habilitar ARK para Artigos: Marcado
Prefixo ARK: ark:16081
Prefixo Personalizado do Sufixo: CRL
URL do Resolvedor: https://n2t.net/

ARK resultante: https://n2t.net/ark:16081/CRL6522-QVWX

### Registro NAAN (n2t.net)

Após instalar o plugin, configure seu target NAAN para:

https://seudominio.com/plugins/pubIds/ark/resolver.php?ark=${value}

Exemplo:
https://revistacarnaubais.com.br/plugins/pubIds/ark/resolver.php?ark=${value}

### Uso

- Clique no botão "Gerar ARK" na seção de "Identificadores" do formulário do artigo
- ARKs duplicados ou inválidos são prevenidos automaticamente

### Integração do Resolvedor

O plugin inclui um resolvedor integrado que funciona sem editar seu arquivo .htaccess principal. O resolvedor:

1. Recebe o sufixo do ARK via parâmetro ?ark=
2. Consulta o banco de dados para encontrar o artigo correspondente
3. Redireciona para a página do artigo (302 Found)

Exemplo de acesso direto:
https://seudominio.com/plugins/pubIds/ark/resolver.php?ark=CRL2244-AABB

### Requisitos

- OJS 3.5.x
- PHP 7.4 ou superior
- MySQL 5.7+ ou MariaDB 10.2+

### Solução de Problemas

Resolvedor retorna 403 Forbbiden/Acesso negado
O plugin inclui um arquivo .htaccess que concede acesso ao resolver.php. Se você ainda receber 403, verifique se seu .htaccess principal tem regras conflitantes.

ARK não aparece no formulário do artigo
Certifique-se de que "Habilitar ARK para Artigos" está marcado nas configurações do plugin.

Erro de ARK duplicado
O plugin previne automaticamente duplicatas. Se você ver este erro, clique em "Gerar ARK" para gerar um novo identificador único.

### Desinstalar

1. Desative o plugin em Configurações > Website > Plugins
2. Remova a pasta ark de plugins/pubIds/
3. (Opcional) Remova os dados ARK do banco de dados usando SQL:
   DELETE FROM publication_settings WHERE setting_name = 'pub-id::ark';

### Licença

GNU General Public License v2 - Consulte o arquivo LICENSE para detalhes.

### Autor

Lury Morais (2026)

### Créditos

Baseado no plugin original pkp-ark-pubid por Yasiel Pérez Vera (2021)

---

[⬆ Voltar ao topo](#português)

---

<a name="español"></a>
## 🇪🇸 Español

**Plugin ARK para OJS 3.5.x**

### Características

- Genera identificadores ARK solo para Artículos
- Formato: ark:NAAN/PERSONALIZADOxxxx-yyyy (ej.: ark:16081/CRL5432-ABCD)
- Prefijo personalizado (2-6 letras mayúsculas)
- Detección y prevención automática de duplicados
- Resolvedor integrado (funciona sin editar el .htaccess principal)
- Listo para registro NAAN (n2t.net)

### Instalación

IMPORTANTE: Cuando descargue el plugin de GitHub, la carpeta puede llamarse ark-plugin-main o ark-plugin-2.0.0. Debe renombrarla a ark antes de copiarla a su instalación de OJS.

1. Descargue el plugin de GitHub
2. Renombre la carpeta a ark (si no se llama ya ark)
3. Copie la carpeta ark a plugins/pubIds/
4. Vaya a Configuraciones > Sitio > Plugins
5. Encuentre el plugin de identificador público "ARK" y actívelo
6. Configure el plugin:
   - Habilitar ARK para Artículos (debe estar marcado)
   - Prefijo ARK: El prefijo fijo (ej.: ark:16081)
   - Prefijo Personalizado del Sufijo: El prefijo antes de la parte aleatoria (2-6 letras, ej.: MIT, UERN, CRL)
   - URL del Resolvedor: https://n2t.net/ (o su propio resolvedor)

### Ejemplo de Configuración

Habilitar ARK para Artículos: Marcado
Prefijo ARK: ark:16081
Prefijo Personalizado del Sufijo: CRL
URL del Resolvedor: https://n2t.net/

ARK resultante: https://n2t.net/ark:16081/CRL6522-QVWX

### Registro NAAN (n2t.net)

Después de instalar el plugin, configure su target NAAN en:

https://sudominio.com/plugins/pubIds/ark/resolver.php?ark=${value}

Ejemplo:
https://revistacarnaubais.com.br/plugins/pubIds/ark/resolver.php?ark=${value}

### Uso

- Haga clic en el botón "Generar ARK" en la sección de Identificadores del formulario del artículo
- Los ARK duplicados o inválidos se previenen automáticamente

### Integración del Resolvedor

El plugin incluye un resolvedor integrado que funciona sin editar su archivo .htaccess principal. El resolvedor:

1. Recibe el sufijo del ARK mediante el parámetro ?ark=
2. Consulta la base de datos para encontrar el artículo correspondiente
3. Redirige a la página del artículo (302 Found)

Ejemplo de acceso directo:
https://sudominio.com/plugins/pubIds/ark/resolver.php?ark=CRL2244-AABB

### Requisitos

- OJS 3.5.x
- PHP 7.4 o superior
- MySQL 5.7+ o MariaDB 10.2+

### Solución de Problemas

El resolvedor devuelve 403 Prohibido
El plugin incluye un archivo .htaccess que concede acceso a resolver.php. Si aún recibe 403, verifique si su .htaccess principal tiene reglas conflictivas.

ARK no aparece en el formulario del artículo
Asegúrese de que "Habilitar ARK para Artículos" esté marcado en la configuración del plugin.

Error de ARK duplicado
El plugin previene automáticamente duplicados. Si ve este error, haga clic en "Generar ARK" para generar un nuevo identificador único.

### Desinstalación

1. Desactive el plugin en Configuraciones > Sitio > Plugins
2. Elimine la carpeta ark de plugins/pubIds/
3. (Opcional) Elimine los datos ARK de la base de datos usando SQL:
   DELETE FROM publication_settings WHERE setting_name = 'pub-id::ark';

### Licencia

GNU General Public License v2 - Consulte el archivo LICENSE para más detalles.

### Autor

Lury Morais (2026)

### Créditos

Basado en el plugin original pkp-ark-pubid por Yasiel Pérez Vera (2021)

---

[⬆ Volver al principio](#español)

---

## 📄 License / Licença / Licencia

GNU General Public License v2 - See LICENSE file for details.

---

**Built for Carnaubais Revista de Literatura** 🚀
