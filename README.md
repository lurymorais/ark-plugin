<a name="top"></a>

# 🌐 ARK Plugin for OJS 3.5.x
> **Escolha seu idioma / Elija su idioma / Choose your language :**  
> [🇧🇷 Português](#portugues) | [🇪🇸 Español](#espanol) | [🇺🇸 English](#english)

---

<a href="https://revistacarnaubais.com.br/ark-telemetry/stats.php">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/endpoint?url=https%3A%2F%2Frevistacarnaubais.com.br%2Fark-telemetry%2Fapi.php%3Ftheme%3Ddark&style=for-the-badge&label=ARKs%20Total%20N.">
    <source media="(prefers-color-scheme: light)" srcset="https://img.shields.io/endpoint?url=https%3A%2F%2Frevistacarnaubais.com.br%2Fark-telemetry%2Fapi.php%3Ftheme%3Dlight&style=for-the-badge&label=ARKs%20Total%20N.">
    <img alt="ARKs Total N." src="https://img.shields.io/endpoint?url=https%3A%2F%2Frevistacarnaubais.com.br%2Fark-telemetry%2Fapi.php%3Ftheme%3Ddark&style=for-the-badge&label=ARKs%20Total%20N.">
  </picture>
</a>


<a name="portugues"></a>
## 🇧🇷 Português

**Plugin ARK para OJS 3.5.x**

### Funcionalidades

- Gera identificadores ARK para **Artigos** e **Edições**
- Formato: `ark:NAAN/SUASIGLAxxxx-yyyy`
- Prefixo personalizado (2-6 letras maiúsculas)
- **Geração automática** ao acessar a aba de identificadores
- **Botão "Gerar Novo ARK"** com confirmação para substituição
- Detecção e prevenção automática de duplicatas
- Resolvedor integrado (funciona sem editar o .htaccess principal)
- Suporte a **metadados ERC** para artigos e edições
- Pronto para registro NAAN (n2t.net)
- **Resolvedor inteligente** que detecta se o ARK é de artigo ou edição

### Instalação

1. Baixe o plugin do GitHub
2. Renomeie a pasta para `ark`
3. Copie a pasta `ark` para `plugins/pubIds/`
4. Vá para **Configurações > Website > Plugins**
5. Encontre o plugin de identificador público "ARK" e ative-o
6. Configure o plugin:

| Configuração | Valor |
|--------------|-------|
| Habilitar ARK para Artigos | ✓ (opcional) |
| Habilitar ARK para Edições | ✓ (opcional) |
| Prefixo ARK | Seu prefixo NAAN (ex.: `ark:12345`) |
| Prefixo Personalizado | 2-6 letras maiúsculas (ex.: `SIGLA`) |
| URL do Resolvedor | `https://n2t.net/` (ou seu próprio resolvedor) |

### Exemplo de Configuração

- **Habilitar ARK para Artigos:** Marcado
- **Habilitar ARK para Edições:** Marcado
- **Prefixo ARK:** `ark:12345`
- **Prefixo Personalizado:** `SIGLA`
- **URL do Resolvedor:** `https://n2t.net/`

**ARK resultante para artigo:** `https://n2t.net/ark:12345/SIGLA1234-ABCD`

**ARK resultante para edição:** `https://n2t.net/ark:12345/SIGLA5678-EFGH`

<img width="auto" height="auto" alt="Image" src="https://github.com/user-attachments/assets/7c3e3a4b-96dc-44c6-a6cb-4dfa2498126f" />

> Ao fim da configuração, você verá um pré-visualizador.

### Compartilhamento de Dados (Opt-out)

Por padrão, o plugin envia dados anônimos de uso mensalmente para ajudar a melhorar o serviço. Você pode desabilitar isso a qualquer momento nas configurações do plugin.

**Dados enviados mensalmente:**
- Identificador NAAN (público)
- Quantidade de ARKs gerados
- Versão do plugin

**Nenhum dado pessoal é coletado**

> Seus dados são protegidos em conformidade com a **LGPD (Lei Geral de Proteção de Dados - Brasil)** e o **GDPR (Regulamento Geral de Proteção de Dados - União Europeia)**.

**Como desabilitar:**
1. Acesse **Configurações > Website > Plugins > ARK**
2. Desmarque a opção **"Enviar estatísticas anônimas"**
3. Clique em Salvar

**Política de Privacidade:** [PRIVACY_POLICY.md](https://github.com/lurymorais/ark-plugin/blob/v3.1.0.0/PRIVACY_POLICY.md)

### Uso

#### Para Artigos
- Acesse o formulário do artigo, aba "Identificadores"
- Clique no botão "Gerar ARK" para gerar um novo identificador
- ARKs duplicados ou inválidos são prevenidos automaticamente

#### Para Edições
- Acesse **Edições > Editar** uma edição
- Vá para a aba **"Identificadores"**
- O ARK é gerado automaticamente ao carregar a página
- Se precisar de um novo ARK, clique em **"Gerar Novo ARK"** (um alerta de confirmação será exibido)
- ARKs duplicados são prevenidos automaticamente

### Resolver e Registro NAAN (se você usa n2t.net) IMPORTANTE

> Após instalar o plugin, configure seu target NAAN para:
> `https://seudominio.com/plugins/pubIds/ark/resolver.php?ark=${value}`

O plugin inclui um **resolvedor integrado** que funciona sem editar seu arquivo .htaccess principal. O resolvedor:

1. Recebe o identificador ARK via parâmetro `?ark=`
2. **Detecta automaticamente** se o ARK pertence a um artigo ou uma edição
3. Redireciona para a página correspondente (302 Found)

### Suporte a Metadados ERC (Inflexões ARK)

O resolvedor suporta inflexões ARK para artigos e edições:

| Inflexão | Comportamento | Exemplo |
|----------|---------------|---------|
| (nenhuma) | Redireciona para o artigo/edição | `?ark=SIGLA0001-ABCD` |
| `?` | Retorna metadados ERC resumidos* | `?ark=SIGLA0001-ABCD?` |
| `??` | Retorna metadados ERC completos | `?ark=SIGLA0001-ABCD??` |
| `.info` | Retorna metadados ERC completos | `?ark=SIGLA0001-ABCD.info` |
| `&info` | Retorna metadados ERC completos | `?ark=SIGLA0001-ABCD&info` |
| `?info` | Retorna metadados ERC completos | `?ark=SIGLA0001-ABCD?info` |

**Exemplo:**
- `https://n2t.net/ark:16081/CRL0001-LURY??` → Retorna metadados ERC

> ***Nota:** A inflexão `?` (metadados resumidos) funciona apenas no acesso direto ao resolvedor, pois em "https://n2t.net/ark:16081/CRL0001-LURY?" o resolver da n2t.net entrega apenas o link sem a inflexão, levando à página do artigo.

### Data de Implementação do ARK

Nas configurações do plugin, você pode definir uma **data de implementação fixa** para o seu periódico:

- Esta data representa quando seu periódico começou a suportar identificadores ARK
- Será exibida no campo `erc-support.when` dos metadados ERC
- A data deve estar no formato `AAAAMMDD` (exemplo: `20260215`)
- Se não for definida, a data de publicação será usada como fallback

### Exibição no Frontend

O ARK é exibido automaticamente em:

- **Página da edição** (issue view)
- **Lista de edições** (issue archive)
- **Página do artigo** (article view)

<img width="349" height="321,5" alt="Image" src="https://github-production-user-asset-6210df.s3.amazonaws.com/243876433/600560870-35e0c164-0ed0-4318-b79d-7d4100fa30ec.png?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAVCODYLSA53PQK4ZA%2F20260531%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20260531T040751Z&X-Amz-Expires=300&X-Amz-Signature=c649ad45c2785056af9a8f687bdafa661c46e52a735d1d26f845f7dbfc164a1b&X-Amz-SignedHeaders=host&response-content-type=image%2Fpng"/>

<img width="201" height="393,5" alt="Image" src="https://github-production-user-asset-6210df.s3.amazonaws.com/243876433/600560926-99a1c758-9b35-44d9-8ed8-6f866f4c315d.png?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAVCODYLSA53PQK4ZA%2F20260531%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20260531T040927Z&X-Amz-Expires=300&X-Amz-Signature=28b49768bb6d2a8a3104d30540f2a2fb66beb894b28efb66615a3e4e3affd434&X-Amz-SignedHeaders=host&response-content-type=image%2Fpng"/>

### Solução de Problemas

#### Resolvedor retorna 403 Forbidden/Acesso negado?
O plugin inclui um arquivo .htaccess que concede acesso ao resolver.php. Se você ainda receber 403, verifique se seu .htaccess principal tem regras conflitantes ou se o arquivo .htaccess do plugin foi removido acidentalmente.

#### Resolvedor redireciona para página 404?
Verifique se o ARK está corretamente salvo no banco de dados e se o prefixo NAAN configurado corresponde ao utilizado no ARK.

#### ARK não aparece no formulário?
Certifique-se de que a opção correspondente (Artigos ou Edições) está marcada nas configurações do plugin e que o plugin está ativo.

#### Erro de ARK duplicado ao salvar?
O plugin previne automaticamente duplicatas verificando tanto artigos quanto edições. Se você encontrar este erro, clique no botão "Gerar Novo ARK" ao lado do campo para criar um identificador único.

#### O botão "Gerar Novo ARK" não aparece?
Recarregue a página (F5) e acesse diretamente a aba para gerar ARK.

#### Metadados ERC retornam data incorreta?
A data de implementação do ARK pode ser configurada nas opções do plugin. Certifique-se de que o campo "Data de Implementação" está preenchido corretamente no formato AAAAMMDD.

### Desinstalar

1. Desative o plugin em **Configurações > Website > Plugins**
2. Remova a pasta `ark` de `plugins/pubIds/`

[Voltar ao topo](#top)

[Licença e créditos](#licenca)

---

<a name="espanol"></a>
## 🇪🇸 Español

**Plugin ARK para OJS 3.5.x**

### Características

- Genera identificadores ARK para **Artículos** y **Ediciones**
- Formato: `ark:NAAN/SUSIGLAxxxx-yyyy`
- Prefijo personalizado (2-6 letras mayúsculas)
- **Generación automática** al acceder a la pestaña de identificadores
- **Botón "Generar Nuevo ARK"** con confirmación para reemplazar
- Detección y prevención automática de duplicados
- Resolvedor integrado (funciona sin editar el .htaccess principal)
- Soporte de **metadatos ERC** para artículos y ediciones
- Listo para registro NAAN (n2t.net)
- **Resolvedor inteligente** que detecta si el ARK es de artículo o edición

### Instalación

1. Descargue el plugin de GitHub
2. Renombre la carpeta a `ark`
3. Copie la carpeta `ark` a `plugins/pubIds/`
4. Vaya a **Configuraciones > Sitio > Plugins**
5. Encuentre el plugin de identificador público "ARK" y actívelo
6. Configure el plugin:

| Configuración | Valor |
|--------------|-------|
| Habilitar ARK para Artículos | ✓ (opcional) |
| Habilitar ARK para Ediciones | ✓ (opcional) |
| Prefijo ARK | Su prefijo NAAN (ej.: `ark:12345`) |
| Prefijo Personalizado | 2-6 letras mayúsculas (ej.: `SIGLA`) |
| URL del Resolvedor | `https://n2t.net/` (o su propio resolvedor) |

### Ejemplo de Configuración

- **Habilitar ARK para Artículos:** Marcado
- **Habilitar ARK para Ediciones:** Marcado
- **Prefijo ARK:** `ark:12345`
- **Prefijo Personalizado:** `SIGLA`
- **URL del Resolvedor:** `https://n2t.net/`

**ARK resultante para artículo:** `https://n2t.net/ark:12345/SIGLA1234-ABCD`

**ARK resultante para edición:** `https://n2t.net/ark:12345/SIGLA5678-EFGH`

<img width="auto" height="auto" alt="Image" src="https://github.com/user-attachments/assets/7c3e3a4b-96dc-44c6-a6cb-4dfa2498126f" />

> Al finalizar la configuración, podrá ver una vista previa de ejemplo de sus recursos.

### Compartir Datos (Opt-out)

Por defecto, el plugin envía datos anónimos de uso mensualmente para ayudar a mejorar el servicio. Puede deshabilitar esto en cualquier momento en la configuración del plugin.

**Datos enviados mensualmente:**
- Identificador NAAN (público)
- Cantidad de ARKs generados
- Versión del plugin

**No se recopilan datos personales**

> Sus datos están protegidos en cumplimiento con la **LGPD (Ley General de Protección de Datos - Brasil)** y el **RGPD (Reglamento General de Protección de Datos - Unión Europea)**.

**Cómo deshabilitar:**
1. Vaya a **Configuraciones > Sitio > Plugins > ARK**
2. Desmarque la opción **"Enviar estadísticas anónimas"**
3. Haga clic en Guardar

**Política de Privacidad:** [PRIVACY_POLICY.md](https://github.com/lurymorais/ark-plugin/blob/v3.1.0.0/PRIVACY_POLICY.md)

### Uso

#### Para Artículos
- Acceda al formulario del artículo, pestaña "Identificadores"
- Haga clic en el botón "Generar ARK" para generar un nuevo identificador
- Los ARK duplicados o inválidos se previenen automáticamente

#### Para Ediciones
- Acceda a **Ediciones > Editar** una edición
- Vaya a la pestaña **"Identificadores"**
- El ARK se genera automáticamente al cargar la página
- Si necesita un nuevo ARK, haga clic en **"Generar Nuevo ARK"** (se mostrará una alerta de confirmación)
- Los ARKs duplicados se previenen automáticamente

### Resolvedor y Registro NAAN (si usa n2t.net) IMPORTANTE

> Después de instalar el plugin, configure su target NAAN en:
> `https://sudominio.com/plugins/pubIds/ark/resolver.php?ark=${value}`

El plugin incluye un **resolvedor integrado** que funciona sin editar su archivo .htaccess principal. El resolvedor:

1. Recibe el identificador ARK mediante el parámetro `?ark=`
2. **Detecta automáticamente** si el ARK pertenece a un artículo o una edición
3. Redirige a la página correspondiente (302 Found)

### Soporte de Metadatos ERC (Inflexiones ARK)

El resolvedor soporta inflexiones ARK para artículos y ediciones:

| Inflexión | Comportamiento | Ejemplo |
|-----------|----------------|---------|
| (ninguna) | Redirige al artículo/edición | `?ark=SIGLA0001-ABCD` |
| `?` | Devuelve metadatos ERC resumidos* | `?ark=SIGLA0001-ABCD?` |
| `??` | Devuelve metadatos ERC completos | `?ark=SIGLA0001-ABCD??` |
| `.info` | Devuelve metadatos ERC completos | `?ark=SIGLA0001-ABCD.info` |
| `&info` | Devuelve metadatos ERC completos | `?ark=SIGLA0001-ABCD&info` |
| `?info` | Devuelve metadatos ERC completos | `?ark=SIGLA0001-ABCD?info` |

**Ejemplo:**
- `https://n2t.net/ark:16081/CRL0001-LURY??` → Devuelve metadatos ERC

> ***Nota:** La inflexión `?` (metadatos resumidos) funciona solo en acceso directo al resolvedor, ya que en `https://n2t.net/ark:16081/CRL0001-LURY?` el resolvedor de n2t.net entrega solo el enlace sin la inflexión, llevando a la página del artículo.

### Fecha de Implementación del ARK

En la configuración del plugin, puede definir una **fecha de implementación fija** para su revista:

- Esta fecha representa cuándo su revista comenzó a soportar identificadores ARK
- Se mostrará en el campo `erc-support.when` de los metadatos ERC
- La fecha debe estar en formato `AAAAMMDD` (ejemplo: `20260215`)
- Si no se define, se usará la fecha de publicación como fallback

### Visualización en el Frontend

El ARK se muestra automáticamente en:

- **Página de la edición** (issue view)
- **Lista de ediciones** (issue archive)
- **Página del artículo** (article view)

<img width="349" height="321,5" alt="Image" src="https://github-production-user-asset-6210df.s3.amazonaws.com/243876433/600560870-35e0c164-0ed0-4318-b79d-7d4100fa30ec.png?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAVCODYLSA53PQK4ZA%2F20260531%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20260531T040751Z&X-Amz-Expires=300&X-Amz-Signature=c649ad45c2785056af9a8f687bdafa661c46e52a735d1d26f845f7dbfc164a1b&X-Amz-SignedHeaders=host&response-content-type=image%2Fpng"/>

<img width="201" height="393,5" alt="Image" src="https://github-production-user-asset-6210df.s3.amazonaws.com/243876433/600560926-99a1c758-9b35-44d9-8ed8-6f866f4c315d.png?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAVCODYLSA53PQK4ZA%2F20260531%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20260531T040927Z&X-Amz-Expires=300&X-Amz-Signature=28b49768bb6d2a8a3104d30540f2a2fb66beb894b28efb66615a3e4e3affd434&X-Amz-SignedHeaders=host&response-content-type=image%2Fpng"/>

### Solución de Problemas

#### ¿El resolvedor devuelve 403 Forbidden/Acceso denegado?
El plugin incluye un archivo .htaccess que concede acceso a resolver.php. Si aún recibe 403, verifique si su .htaccess principal tiene reglas conflictivas o si el archivo .htaccess del plugin fue eliminado accidentalmente.

#### ¿El resolvedor redirige a página 404?
Verifique que el ARK esté correctamente guardado en la base de datos y que el prefijo NAAN configurado coincida con el utilizado en el ARK.

#### ¿El ARK no aparece en el formulario?
Asegúrese de que la opción correspondiente (Artículos o Ediciones) esté marcada en la configuración del plugin y que el plugin esté activo.

#### ¿Error de ARK duplicado al guardar?
El plugin previene automáticamente duplicados verificando tanto artículos como ediciones. Si encuentra este error, haga clic en el botón "Generar Nuevo ARK" junto al campo para crear un identificador único.

#### ¿El botón "Generar Nuevo ARK" no aparece?
Recargue la página (F5) y acceda directamente a la pestaña para generar el ARK.

#### ¿Los metadatos ERC devuelven fecha incorrecta?
La fecha de implementación del ARK se puede configurar en las opciones del plugin. Asegúrese de que el campo "Fecha de Implementación" esté correctamente completado en el formato AAAAMMDD.

### Desinstalar

1. Desactive el plugin en **Configuraciones > Sitio > Plugins**
2. Elimine la carpeta `ark` de `plugins/pubIds/`

[Volver al principio](#top)

[Licencia y créditos](#licenca)

---

<a name="english"></a>
## 🇺🇸 English

**Archival Resource Key (ARK) Plugin for OJS 3.5.x**

### Features

- Generates ARK identifiers for **Articles** and **Issues**
- Format: `ark:NAAN/YOURPREFIXxxxx-yyyy`
- Customizable prefix (2-6 uppercase letters)
- **Automatic generation** when accessing the identifiers tab
- **"Generate New ARK" button** with confirmation for replacement
- Automatic duplicate detection and prevention
- Built-in resolver (works without editing main .htaccess)
- **ERC metadata support** for articles and issues
- Ready for NAAN registration (n2t.net)
- **Smart resolver** that detects whether the ARK belongs to an article or issue

### Installation

1. Download the plugin from GitHub
2. Rename the folder to `ark`
3. Copy the `ark` folder to `plugins/pubIds/`
4. Go to **Settings > Website > Plugins**
5. Find the "ARK" Public Identifier Plugin and enable it
6. Configure the plugin:

| Setting | Value |
|---------|-------|
| Enable ARK for Articles | ✓ (optional) |
| Enable ARK for Issues | ✓ (optional) |
| ARK Prefix | Your NAAN prefix (e.g., `ark:12345`) |
| Custom Prefix | 2-6 uppercase letters (e.g., `PREFIX`) |
| Resolver URL | `https://n2t.net/` (or your own resolver) |

### Configuration Example

- **Enable ARK for Articles:** Checked
- **Enable ARK for Issues:** Checked
- **ARK Prefix:** `ark:12345`
- **Custom Prefix:** `PREFIX`
- **Resolver URL:** `https://n2t.net/`

**Resulting ARK for article:** `https://n2t.net/ark:12345/PREFIX1234-ABCD`

**Resulting ARK for issue:** `https://n2t.net/ark:12345/PREFIX5678-EFGH`

<img width="auto" height="auto" alt="Image" src="https://github.com/user-attachments/assets/7c3e3a4b-96dc-44c6-a6cb-4dfa2498126f" />

> At the end of the configuration, you will be able to see an example preview for your resources.

### Data Sharing (Opt-out)

By default, the plugin sends anonymous usage data monthly to help improve the service. You can disable this at any time in the plugin settings.

**Data sent monthly:**
- NAAN identifier (public)
- Number of ARKs generated
- Plugin version

**No personal data is collected**

> Your data is protected in compliance with **LGPD (Brazilian General Data Protection Law)** and **GDPR (General Data Protection Regulation - European Union)**.

**How to disable:**
1. Go to **Settings > Website > Plugins > ARK**
2. Uncheck **"Send anonymous statistics"**
3. Click Save

**Privacy Policy:** [PRIVACY_POLICY.md](https://github.com/lurymorais/ark-plugin/blob/v3.1.0.0/PRIVACY_POLICY.md)

### Usage

#### For Articles
- Access the article form, "Identifiers" tab
- Click the "Generate ARK" button to create a new identifier
- Duplicate or invalid ARKs are automatically prevented

#### For Issues
- Access **Issues > Edit** an issue
- Go to the **"Identifiers"** tab
- The ARK is automatically generated when the page loads
- If you need a new ARK, click the **"Generate New ARK"** button (a confirmation alert will be shown)
- Duplicate ARKs are automatically prevented

### Resolver and NAAN Registration (if using n2t.net) IMPORTANT

> After installing the plugin, configure your NAAN target to:
> `https://yourdomain.com/plugins/pubIds/ark/resolver.php?ark=${value}`

The plugin includes a **built-in resolver** that works without editing your main .htaccess file. The resolver:

1. Receives the ARK identifier via the `?ark=` parameter
2. **Automatically detects** whether the ARK belongs to an article or an issue
3. Redirects to the corresponding page (302 Found)

### ERC Metadata Support (ARK Inflections)

The resolver supports ARK inflections for articles and issues:

| Inflection | Behavior | Example |
|------------|----------|---------|
| (none) | Redirects to article/issue | `?ark=PREFIX0001-ABCD` |
| `?` | Returns brief ERC metadata* | `?ark=PREFIX0001-ABCD?` |
| `??` | Returns full ERC metadata | `?ark=PREFIX0001-ABCD??` |
| `.info` | Returns full ERC metadata | `?ark=PREFIX0001-ABCD.info` |
| `&info` | Returns full ERC metadata | `?ark=PREFIX0001-ABCD&info` |
| `?info` | Returns full ERC metadata | `?ark=PREFIX0001-ABCD?info` |

**Example:**
- `https://n2t.net/ark:16081/CRL0001-LURY??` → Returns ERC metadata

> ***Note:** The `?` inflection (brief metadata) only works when accessing your resolver directly, because at `https://n2t.net/ark:16081/CRL0001-LURY?` the n2t.net resolver delivers only the link without the inflection, leading to the article page.

### ARK Implementation Date

In the plugin settings, you can set a **fixed implementation date** for your journal:

- This date represents when your journal started supporting ARK identifiers
- It will be displayed in the `erc-support.when` field of the ERC metadata
- The date must be in `YYYYMMDD` format (e.g., `20260215`)
- If not set, the publication date is used as fallback

### Frontend Display

The ARK is automatically displayed on:

- **Issue page** (issue view)
- **Issue archive list** (issue archive)
- **Article page** (article view)

<img width="349" height="321,5" alt="Image" src="https://github-production-user-asset-6210df.s3.amazonaws.com/243876433/600560870-35e0c164-0ed0-4318-b79d-7d4100fa30ec.png?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAVCODYLSA53PQK4ZA%2F20260531%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20260531T040751Z&X-Amz-Expires=300&X-Amz-Signature=c649ad45c2785056af9a8f687bdafa661c46e52a735d1d26f845f7dbfc164a1b&X-Amz-SignedHeaders=host&response-content-type=image%2Fpng"/>

<img width="201" height="393,5" alt="Image" src="https://github-production-user-asset-6210df.s3.amazonaws.com/243876433/600560926-99a1c758-9b35-44d9-8ed8-6f866f4c315d.png?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAVCODYLSA53PQK4ZA%2F20260531%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20260531T040927Z&X-Amz-Expires=300&X-Amz-Signature=28b49768bb6d2a8a3104d30540f2a2fb66beb894b28efb66615a3e4e3affd434&X-Amz-SignedHeaders=host&response-content-type=image%2Fpng"/>

### Troubleshooting  

#### Does the resolver return 403 Forbidden/Access denied?
The plugin includes a .htaccess file that grants access to resolver.php. If you still get 403, check if your main .htaccess has conflicting rules or if the plugin's .htaccess file was accidentally removed.

#### Does the resolver redirect to a 404 page?
Verify that the ARK is correctly saved in the database and that the configured NAAN prefix matches the one used in the ARK.

#### Does the ARK not appear in the form?
Make sure the corresponding option (Articles or Issues) is checked in the plugin settings and that the plugin is active.

#### Getting a duplicate ARK error when saving?
The plugin automatically prevents duplicates by checking both articles and issues. If you encounter this error, click the "Generate New ARK" button next to the field to create a unique identifier.

#### Does the "Generate New ARK" button not appear?
Refresh the page (F5) and directly access the tab to generate the ARK.

#### Does ERC metadata return the wrong date?
The ARK implementation date can be configured in the plugin options. Make sure the "Implementation Date" field is correctly filled in YYYYMMDD format.

### Uninstallation

1. Disable the plugin in **Settings > Website > Plugins**
2. Remove the `ark` folder from `plugins/pubIds/`

[Back to top](#top)

---

<a name="licenca"></a>

<div style="text-align:center;">

## Licença / Licencia / License

GNU General Public License v2.0

[LICENSE](https://github.com/lurymorais/ark-plugin/blob/main/LICENSE)

---

### Créditos / Credits

Baseado no | Basado en el |
Based on<br>

plugin **pkp-ark-pubid** © Yasiel Pérez Vera (2021)

---

<img src="https://revistacarnaubais.com.br/imgs/favicon.png" width="64" height="64" style="width: 64px; height: 64px;">

<strong>Carnaubais Revista de Literatura</strong>

---

<sub>Lury Morais © 2026</sub>
</div>
