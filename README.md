<a name="top"></a>

# 🌐 ARK Plugin for OJS 3.5.x

[![AI-DECLARATION: copilot](https://img.shields.io/badge/䷼%20AI--DECLARATION-copilot-fee2e2?labelColor=fee2e2)](https://ai-declaration.md)

> **Escolha seu idioma / Elija su idioma / Choose your language :**  
> [🇧🇷 Português](#portugues) | [🇪🇸 Español](#espanol) | [🇺🇸🇪 English](#english)

---

<a href="https://revistacarnaubais.com.br/ark-telemetry/stats.php">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/endpoint?url=https%3A%2F%2Frevistacarnaubais.com.br%2Fark-telemetry%2Fapi.php%3Ftheme%3Ddark&style=for-the-badge&label=ARKs%20Total%20N.">
    <source media="(prefers-color-scheme: light)" srcset="https://img.shields.io/endpoint?url=https%3A%2F%2Frevistacarnaubais.com.br%2Fark-telemetry%2Fapi.php%3Ftheme%3Dlight&style=for-the-badge&label=ARKs%20Total%20N.">
    <img alt="ARKs Total N." src="https://img.shields.io/endpoint?url=https%3A%2F%2Frevistacarnaubais.com.br%2Fark-telemetry%2Fapi.php%3Ftheme%3Ddark&style=for-the-badge&label=ARKs%20Total%20N.">
  </picture>
</a>

# ARK Plugin for OJS 3.5.x

<a name="portugues"></a>
## 🇧🇷 Português

**Plugin ARK para OJS 3.5.x**

### Funcionalidades

- Gera identificadores ARK para Artigos e Edições
- Formato: `ark:NAAN/SUASIGLAxxxx-yyyy`
- Prefixo personalizado (2-6 letras maiúsculas)
- Geração automática ao acessar a aba de identificadores
- Botão "Gerar Novo ARK" com confirmação para substituição
- Detecção e prevenção automática de duplicatas
- Resolvedor integrado (funciona sem editar o `.htaccess` principal)
- Suporte a metadados ERC para artigos e edições
- Pronto para registro NAAN (n2t.net)
- Verificação de propriedade do NAAN via n2t.net

### Instalação

**Via arquivo (tar.gz/zip)**

1. Baixe o arquivo da página de releases do GitHub
2. Acesse Configurações > Website > Plugins > Upload Plugin
3. Selecione o arquivo baixado e faça o upload
4. Ative o plugin

**Via Git clone**

```bash
cd plugins/pubIds/
git clone https://github.com/lurymorais/ark-plugin.git ark
```

**Via download manual**

1. Baixe o plugin do GitHub
2. Renomeie a pasta para `ark`
3. Copie a pasta `ark` para `plugins/pubIds/`
4. Acesse Configurações > Website > Plugins
5. Encontre o plugin "ARK" e ative-o

Após a instalação, configure o plugin:

- Habilitar ARK para Artigos: (opcional)
- Habilitar ARK para Edições: (opcional)
- Prefixo ARK: Seu prefixo NAAN (ex.: `ark:12345`)
- Prefixo Personalizado: 2-6 letras maiúsculas (ex.: `SIGLA`)
- URL do Resolvedor: `https://n2t.net/` (ou seu próprio resolvedor)

### Telemetria (Compartilhamento de Dados)

O plugin envia estatísticas anônimas de uso por padrão para ajudar a melhorar o serviço. Você pode desativar a qualquer momento nas configurações.

**Dados enviados (padrão, a menos que desativado):**

- **NAAN**: Identificador público do periódico (ex.: `ark:16081`)
- **ARKs Count**: Total de ARKs gerados (ex.: `1250`)
- **Plugin Version**: Versão do plugin em uso (ex.: `3.1.0.0`)

**Nenhum dado pessoal é coletado:**
- Sem e-mails, nomes, IPs, dados de usuários ou conteúdo

**Como funciona:**
1. A telemetria está ativada por padrão (opt-out)
2. Você pode desativar nas configurações do plugin
3. Os dados são enviados mensalmente via tarefa agendada do OJS
4. Apenas números agregados são publicados publicamente

**Dashboard Público:** https://revistacarnaubais.com.br/ark-telemetry/stats.php

**Política de Privacidade:** [PRIVACY_POLICY.md](https://github.com/lurymorais/ark-plugin/blob/main/PRIVACY_POLICY.md)

### Verificação de Identidade e Segurança

O plugin utiliza um sistema de verificação em duas camadas para garantir que apenas revistas legítimas possam enviar dados de telemetria.

**1. Verificação por Arquivo (identity.txt)**

O plugin cria automaticamente um arquivo `identity.txt` na pasta do plugin durante a instalação. Este arquivo serve como prova de que o plugin está realmente instalado no domínio.

- O arquivo é criado automaticamente ao ativar o plugin
- O servidor de telemetria verifica se o arquivo existe no domínio antes de aceitar dados
- Impede que atores externos enviem dados em nome da revista

**2. Verificação por Chave Privada**

Além do arquivo de identidade, o plugin gera uma chave privada única durante a instalação.

- Uma chave única é gerada e armazenada no banco de dados OJS
- A chave é registrada no servidor de telemetria durante a configuração
- Cada envio de estatísticas requer a chave privada para autenticação
- A chave é comparada usando `hash_equals()` para prevenir ataques de timing

**Por que duas camadas?**

- `identity.txt`: Verifica que o plugin existe no domínio
- Chave privada: Verifica que o envio vem da revista legítima
- Juntas, impedem falsificação e acesso não autorizado

### Uso

**Para Artigos**

- Acesse o formulário do artigo, aba "Identificadores"
- Clique no botão "Gerar ARK" para gerar um novo identificador
- ARKs duplicados ou inválidos são prevenidos automaticamente

**Para Edições**

- Acesse Edições > Editar uma edição
- Vá para a aba "Identificadores"
- O ARK é gerado automaticamente ao carregar a página
- Se precisar de um novo ARK, clique em "Gerar Novo ARK" (um alerta de confirmação será exibido)
- ARKs duplicados são prevenidos automaticamente

### Resolvedor e Registro NAAN (importante)

Após instalar o plugin, configure seu target NAAN para:

```
https://seudominio.com/plugins/pubIds/ark/resolver.php?ark=${value}
```

O plugin inclui um resolvedor integrado que funciona sem editar seu arquivo `.htaccess` principal. O resolvedor:

1. Recebe o identificador ARK via parâmetro `?ark=`
2. Detecta automaticamente se o ARK pertence a um artigo ou uma edição
3. Redireciona para a página correspondente (302 Found)

### Suporte a Metadados ERC (Inflexões ARK)

O resolvedor suporta inflexões ARK para artigos e edições:

- (nenhuma): Redireciona para o artigo/edição (ex.: `?ark=SIGLA0001-ABCD`)
- `?`: Retorna metadados ERC resumidos (ex.: `?ark=SIGLA0001-ABCD?`)**
- `??`: Retorna metadados ERC completos (ex.: `?ark=SIGLA0001-ABCD??`)
- `.info`: Retorna metadados ERC completos (ex.: `?ark=SIGLA0001-ABCD.info`)
- `&info`: Retorna metadados ERC completos (ex.: `?ark=SIGLA0001-ABCD&info`)
- `?info`: Retorna metadados ERC completos (ex.: `?ark=SIGLA0001-ABCD?info`)

Exemplo:
```
https://n2t.net/ark:16081/CRL0001-LURY??
```
→ Retorna metadados ERC

**Nota: A inflexão `?` (metadados resumidos) funciona apenas no acesso direto ao resolvedor.

### Data de Implementação do ARK

Nas configurações do plugin, você pode definir uma data de implementação fixa para o seu periódico:

- Representa quando seu periódico começou a suportar identificadores ARK
- Será exibida no campo `erc-support.when` dos metadados ERC
- Formato: `AAAAMMDD` (exemplo: `20260215`)
- Se não for definida, a data de publicação será usada como fallback

### Exibição no Frontend

O ARK é exibido automaticamente em:

- Página da edição (issue view)
- Lista de edições (issue archive)
- Página do artigo (article view)

### Solução de Problemas

**Resolvedor retorna 403 Forbidden/Acesso negado?**

O plugin inclui um arquivo `.htaccess` que concede acesso ao `resolver.php`. Se você ainda receber 403, verifique se seu `.htaccess` principal tem regras conflitantes ou se o arquivo `.htaccess` do plugin foi removido acidentalmente.

**Resolvedor redireciona para página 404?**

Verifique se o ARK está corretamente salvo no banco de dados e se o prefixo NAAN configurado corresponde ao utilizado no ARK.

**ARK não aparece no formulário?**

Certifique-se de que a opção correspondente (Artigos ou Edições) está marcada nas configurações do plugin e que o plugin está ativo.

**Erro de ARK duplicado ao salvar?**

O plugin previne automaticamente duplicatas verificando tanto artigos quanto edições. Se você encontrar este erro, clique no botão "Gerar Novo ARK" ao lado do campo para criar um identificador único.

**Botão "Gerar Novo ARK" não aparece?**

Recarregue a página (F5) e acesse diretamente a aba para gerar ARK.

**Metadados ERC retornam data incorreta?**

A data de implementação do ARK pode ser configurada nas opções do plugin. Certifique-se de que o campo "Data de Implementação" está preenchido corretamente no formato `AAAAMMDD`.

### Desinstalar

1. Desative o plugin em Configurações > Website > Plugins
2. Remova a pasta `ark` de `plugins/pubIds/`

[Voltar ao topo](#top) \
[Licença](#licenca)

---

<a name="espanol"></a>
## 🇪🇸 Español

**Plugin ARK para OJS 3.5.x**

### Funcionalidades

- Genera identificadores ARK para Artículos y Números
- Formato: `ark:NAAN/TUSIGLAxxxx-yyyy`
- Prefijo personalizado (2-6 letras mayúsculas)
- Generación automática al acceder a la pestaña de identificadores
- Botón "Generar Nuevo ARK" con confirmación para sustitución
- Detección y prevención automática de duplicados
- Resolvedor integrado (funciona sin editar el `.htaccess` principal)
- Soporte para metadatos ERC para artículos y números
- Listo para registro NAAN (n2t.net)
- Verificación de propiedad del NAAN vía n2t.net

### Instalación

**Vía archivo (tar.gz/zip)**

1. Descargue el archivo desde la página de releases de GitHub
2. Acceda a Configuraciones > Sitio Web > Plugins > Subir Plugin
3. Seleccione el archivo descargado y realice la subida
4. Active el plugin

**Vía Git clone**

```bash
cd plugins/pubIds/
git clone https://github.com/lurymorais/ark-plugin.git ark
```

**Vía descarga manual**

1. Descargue el plugin desde GitHub
2. Renombre la carpeta a `ark`
3. Copie la carpeta `ark` a `plugins/pubIds/`
4. Acceda a Configuraciones > Sitio Web > Plugins
5. Encuentre el plugin "ARK" y actívelo

Después de la instalación, configure el plugin:

- Habilitar ARK para Artículos: (opcional)
- Habilitar ARK para Números: (opcional)
- Prefijo ARK: Su prefijo NAAN (ej.: `ark:12345`)
- Prefijo Personalizado: 2-6 letras mayúsculas (ej.: `SIGLA`)
- URL del Resolvedor: `https://n2t.net/` (o su propio resolvedor)

### Telemetría (Compartición de Datos)

El plugin envía estadísticas anónimas de uso por defecto para ayudar a mejorar el servicio. Puede desactivarlas en cualquier momento en las configuraciones.

**Datos enviados (por defecto, a menos que se desactive):**

- **NAAN**: Identificador público de la revista (ej.: `ark:16081`)
- **ARKs Count**: Total de ARKs generados (ej.: `1250`)
- **Plugin Version**: Versión del plugin en uso (ej.: `3.1.0.0`)

**No se recopilan datos personales:**
- Sin correos electrónicos, nombres, IPs, datos de usuarios o contenido

**Cómo funciona:**
1. La telemetría está activada por defecto (opt-out)
2. Puede desactivarla en las configuraciones del plugin
3. Los datos se envían mensualmente vía tarea programada de OJS
4. Solo números agregados se publican públicamente

**Dashboard Público:** https://revistacarnaubais.com.br/ark-telemetry/stats.php

**Política de Privacidad:** [PRIVACY_POLICY.md](https://github.com/lurymorais/ark-plugin/blob/main/PRIVACY_POLICY.md)

### Verificación de Identidad y Seguridad

El plugin utiliza un sistema de verificación en dos capas para garantizar que solo revistas legítimas puedan enviar datos de telemetría.

**1. Verificación por Archivo (identity.txt)**

El plugin crea automáticamente un archivo `identity.txt` en la carpeta del plugin durante la instalación. Este archivo sirve como prueba de que el plugin está realmente instalado en el dominio.

- El archivo se crea automáticamente al activar el plugin
- El servidor de telemetría verifica que el archivo existe en el dominio antes de aceptar datos
- Impide que actores externos envíen datos en nombre de la revista

**2. Verificación por Clave Privada**

Además del archivo de identidad, el plugin genera una clave privada única durante la instalación.

- Una clave única se genera y almacena en la base de datos OJS
- La clave se registra en el servidor de telemetría durante la configuración
- Cada envío de estadísticas requiere la clave privada para autenticación
- La clave se compara usando `hash_equals()` para prevenir ataques de timing

**¿Por qué dos capas?**

- `identity.txt`: Verifica que el plugin existe en el dominio
- Clave privada: Verifica que el envío proviene de la revista legítima
- Juntas, previenen falsificación y acceso no autorizado

### Uso

**Para Artículos**

- Acceda al formulario del artículo, pestaña "Identificadores"
- Haga clic en el botón "Generar ARK" para generar un nuevo identificador
- Los ARKs duplicados o inválidos se previenen automáticamente

**Para Números**

- Acceda a Números > Editar un número
- Vaya a la pestaña "Identificadores"
- El ARK se genera automáticamente al cargar la página
- Si necesita un nuevo ARK, haga clic en "Generar Nuevo ARK" (se mostrará una alerta de confirmación)
- Los ARKs duplicados se previenen automáticamente

### Resolvedor y Registro NAAN (importante)

Después de instalar el plugin, configure su target NAAN para:

```
https://sudominio.com/plugins/pubIds/ark/resolver.php?ark=${value}
```

El plugin incluye un resolvedor integrado que funciona sin editar su archivo `.htaccess` principal. El resolvedor:

1. Recibe el identificador ARK vía parámetro `?ark=`
2. Detecta automáticamente si el ARK pertenece a un artículo o a un número
3. Redirige a la página correspondiente (302 Found)

### Soporte para Metadatos ERC (Inflexiones ARK)

El resolvedor soporta inflexiones ARK para artículos y números:

- (ninguna): Redirige al artículo/número (ej.: `?ark=SIGLA0001-ABCD`)
- `?`: Retorna metadatos ERC resumidos (ej.: `?ark=SIGLA0001-ABCD?`)**
- `??`: Retorna metadatos ERC completos (ej.: `?ark=SIGLA0001-ABCD??`)
- `.info`: Retorna metadatos ERC completos (ej.: `?ark=SIGLA0001-ABCD.info`)
- `&info`: Retorna metadatos ERC completos (ej.: `?ark=SIGLA0001-ABCD&info`)
- `?info`: Retorna metadatos ERC completos (ej.: `?ark=SIGLA0001-ABCD?info`)

Ejemplo:
```
https://n2t.net/ark:16081/CRL0001-LURY??
```
→ Retorna metadatos ERC

**Nota: La inflexión `?` (metadatos resumidos) funciona solo en el acceso directo al resolvedor.

### Fecha de Implementación del ARK

En las configuraciones del plugin, puede definir una fecha de implementación fija para su revista:

- Representa cuándo su revista comenzó a soportar identificadores ARK
- Se mostrará en el campo `erc-support.when` de los metadatos ERC
- Formato: `AAAAMMDD` (ejemplo: `20260215`)
- Si no se define, se usará la fecha de publicación como fallback

### Visualización en el Frontend

El ARK se muestra automáticamente en:

- Página del número (issue view)
- Lista de números (issue archive)
- Página del artículo (article view)

### Solución de Problemas

**¿El resolvedor devuelve 403 Forbidden/Acceso denegado?**

El plugin incluye un archivo `.htaccess` que concede acceso a `resolver.php`. Si aún recibe 403, verifique si su `.htaccess` principal tiene reglas conflictivas o si el archivo `.htaccess` del plugin fue eliminado accidentalmente.

**¿El resolvedor redirige a página 404?**

Verifique que el ARK esté correctamente guardado en la base de datos y que el prefijo NAAN configurado coincida con el utilizado en el ARK.

**¿El ARK no aparece en el formulario?**

Asegúrese de que la opción correspondiente (Artículos o Números) esté marcada en las configuraciones del plugin y que el plugin esté activo.

**¿Error de ARK duplicado al guardar?**

El plugin previene automáticamente duplicados verificando tanto artículos como números. Si encuentra este error, haga clic en el botón "Generar Nuevo ARK" junto al campo para crear un identificador único.

**¿El botón "Generar Nuevo ARK" no aparece?**

Recargue la página (F5) y acceda directamente a la pestaña para generar ARK.

**¿Los metadatos ERC devuelven fecha incorrecta?**

La fecha de implementación del ARK puede configurarse en las opciones del plugin. Asegúrese de que el campo "Fecha de Implementación" esté correctamente llenado en el formato `AAAAMMDD`.

### Desinstalar

1. Desactive el plugin en Configuraciones > Sitio Web > Plugins
2. Elimine la carpeta `ark` de `plugins/pubIds/`

[Volver a inicio](#top) \
[Licencia](#licenca)

---

<a name="english"></a>
## 🇺🇸 English

**ARK Plugin for OJS 3.5.x**

### Features

- Generates ARK identifiers for Articles and Issues
- Format: `ark:NAAN/YOURPREFIXxxxx-yyyy`
- Custom prefix (2-6 uppercase letters)
- Automatic generation when accessing the identifiers tab
- "Generate New ARK" button with confirmation for replacement
- Automatic duplicate detection and prevention
- Integrated resolver (works without editing the main `.htaccess`)
- ERC metadata support for articles and issues
- Ready for NAAN registration (n2t.net)
- NAAN ownership verification via n2t.net

### Installation

**Via file (tar.gz/zip)**

1. Download the file from the GitHub releases page
2. Go to Settings > Website > Plugins > Upload Plugin
3. Select the downloaded file and upload it
4. Enable the plugin

**Via Git clone**

```bash
cd plugins/pubIds/
git clone https://github.com/lurymorais/ark-plugin.git ark
```

**Via manual download**

1. Download the plugin from GitHub
2. Rename the folder to `ark`
3. Copy the `ark` folder to `plugins/pubIds/`
4. Go to Settings > Website > Plugins
5. Find the "ARK" plugin and enable it

After installation, configure the plugin:

- Enable ARK for Articles: (optional)
- Enable ARK for Issues: (optional)
- ARK Prefix: Your NAAN prefix (e.g.: `ark:12345`)
- Custom Prefix: 2-6 uppercase letters (e.g.: `SIGLA`)
- Resolver URL: `https://n2t.net/` (or your own resolver)

### Telemetry (Data Sharing)

The plugin sends anonymous usage statistics by default to help improve the service. You can disable it at any time in the settings.

**Data sent (default, unless disabled):**

- **NAAN**: Public journal identifier (e.g.: `ark:16081`)
- **ARKs Count**: Total ARKs generated (e.g.: `1250`)
- **Plugin Version**: Plugin version in use (e.g.: `3.1.0.0`)

**No personal data is collected:**
- No emails, names, IPs, user data, or content

**How it works:**
1. Telemetry is enabled by default (opt-out)
2. You can disable it in the plugin settings
3. Data is sent monthly via OJS scheduled task
4. Only aggregated numbers are published publicly

**Public Dashboard:** https://revistacarnaubais.com.br/ark-telemetry/stats.php

**Privacy Policy:** [PRIVACY_POLICY.md](https://github.com/lurymorais/ark-plugin/blob/main/PRIVACY_POLICY.md)

### Identity and Security Verification

The plugin uses a two-layer verification system to ensure that only legitimate journals can send telemetry data.

**1. File Verification (identity.txt)**

The plugin automatically creates an `identity.txt` file in the plugin folder during installation. This file serves as proof that the plugin is actually installed on the domain.

- The file is automatically created when enabling the plugin
- The telemetry server verifies that the file exists on the domain before accepting data
- Prevents external actors from sending data on behalf of the journal

**2. Private Key Verification**

In addition to the identity file, the plugin generates a unique private key during installation.

- A unique key is generated and stored in the OJS database
- The key is registered with the telemetry server during configuration
- Each statistics submission requires the private key for authentication
- The key is compared using `hash_equals()` to prevent timing attacks

**Why two layers?**

- `identity.txt`: Verifies that the plugin exists on the domain
- Private key: Verifies that the submission comes from the legitimate journal
- Together, they prevent forgery and unauthorized access

### Usage

**For Articles**

- Access the article form, "Identifiers" tab
- Click the "Generate ARK" button to generate a new identifier
- Duplicate or invalid ARKs are automatically prevented

**For Issues**

- Go to Issues > Edit an issue
- Go to the "Identifiers" tab
- The ARK is automatically generated when loading the page
- If you need a new ARK, click "Generate New ARK" (a confirmation alert will be shown)
- Duplicate ARKs are automatically prevented

### Resolver and NAAN Registration (important)

After installing the plugin, configure your NAAN target to:

```
https://yourdomain.com/plugins/pubIds/ark/resolver.php?ark=${value}
```

The plugin includes an integrated resolver that works without editing your main `.htaccess` file. The resolver:

1. Receives the ARK identifier via `?ark=` parameter
2. Automatically detects whether the ARK belongs to an article or an issue
3. Redirects to the corresponding page (302 Found)

### ERC Metadata Support (ARK Inflections)

The resolver supports ARK inflections for articles and issues:

- (none): Redirects to the article/issue (e.g.: `?ark=SIGLA0001-ABCD`)
- `?`: Returns summarized ERC metadata (e.g.: `?ark=SIGLA0001-ABCD?`)**
- `??`: Returns complete ERC metadata (e.g.: `?ark=SIGLA0001-ABCD??`)
- `.info`: Returns complete ERC metadata (e.g.: `?ark=SIGLA0001-ABCD.info`)
- `&info`: Returns complete ERC metadata (e.g.: `?ark=SIGLA0001-ABCD&info`)
- `?info`: Returns complete ERC metadata (e.g.: `?ark=SIGLA0001-ABCD?info`)

Example:
```
https://n2t.net/ark:16081/CRL0001-LURY??
```
→ Returns ERC metadata

**Note: The `?` inflection (summarized metadata) only works when accessing the resolver directly.

### ARK Implementation Date

In the plugin settings, you can set a fixed implementation date for your journal:

- Represents when your journal started supporting ARK identifiers
- Will be displayed in the `erc-support.when` field of ERC metadata
- Format: `YYYYMMDD` (example: `20260215`)
- If not set, the publication date will be used as fallback

### Frontend Display

The ARK is automatically displayed in:

- Issue page (issue view)
- Issue list (issue archive)
- Article page (article view)

### Troubleshooting

**Resolver returns 403 Forbidden/Access denied?**

The plugin includes a `.htaccess` file that grants access to `resolver.php`. If you still receive 403, check if your main `.htaccess` has conflicting rules or if the plugin's `.htaccess` file was accidentally removed.

**Resolver redirects to 404 page?**

Verify that the ARK is correctly saved in the database and that the configured NAAN prefix matches the one used in the ARK.

**ARK doesn't appear in the form?**

Make sure the corresponding option (Articles or Issues) is checked in the plugin settings and that the plugin is active.

**Duplicate ARK error when saving?**

The plugin automatically prevents duplicates by checking both articles and issues. If you encounter this error, click the "Generate New ARK" button next to the field to create a unique identifier.

**"Generate New ARK" button doesn't appear?**

Reload the page (F5) and directly access the tab to generate ARK.

**ERC metadata returns incorrect date?**

The ARK implementation date can be configured in the plugin options. Make sure the "Implementation Date" field is correctly filled in the `YYYYMMDD` format.

### Uninstall

1. Disable the plugin in Settings > Website > Plugins
2. Remove the `ark` folder from `plugins/pubIds/`

---

[Back to top](#top)

---

<a name="licenca"></a>

<div style="text-align:center;">

## Licença / Licencia / License

GNU General Public License v2.0

[LICENSE](https://github.com/lurymorais/ark-plugin/blob/main/LICENSE)

---

<img src="https://revistacarnaubais.com.br/imgs/favicon.png" width="64" height="64" style="width: 64px; height: 64px;">

<strong>Carnaubais Revista de Literatura</strong>

---

<sub>Lury Morais © 2026</sub>
</div>
