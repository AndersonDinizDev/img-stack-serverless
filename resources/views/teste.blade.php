<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMG-STACK - Processamento de Imagens</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .image-wrapper {
            position: relative;
            height: 200px;
            overflow: hidden;
            background-color: #e9ecef;
        }

        .image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-wrapper img.show {
            opacity: 1;
        }

        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        .skeleton-line {
            height: 0.75rem;
            margin-bottom: 0.5rem;
            animation: shimmer 1.5s infinite;
            background: linear-gradient(90deg, #e0e0e0 25%, #d0d0d0 50%, #e0e0e0 75%);
            background-size: 200% 100%;
        }

        .status-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            font-size: 0.75rem;
        }

        .nav-tabs .nav-link {
            color: #495057;
            border: none;
            border-bottom: 2px solid transparent;
        }

        .nav-tabs .nav-link:hover {
            border-color: #dee2e6;
        }

        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background-color: transparent;
            border-color: #0d6efd;
        }

        pre {
            margin: 0;
            border-radius: 0.375rem;
        }

        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold">IMG-STACK</h1>
        <p class="lead text-muted">Processamento inteligente de imagens em tempo real</p>
    </div>

    <div class="row mb-5">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Configurações</h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label">Largura</label>
                            <input type="number" class="form-control" id="width" value="800" min="100" max="2000">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Altura</label>
                            <input type="number" class="form-control" id="height" value="600" min="100" max="2000">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Formato</label>
                            <select class="form-select" id="format">
                                <option value="webp">WebP</option>
                                <option value="jpg">JPEG</option>
                                <option value="png">PNG</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Qualidade</label>
                            <input type="number" class="form-control" id="quality" value="85" min="60" max="100">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="detectFaces">
                                <label class="form-check-label" for="detectFaces">
                                    Detectar rostos
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="detectUnsafe">
                                <label class="form-check-label" for="detectUnsafe">
                                    Detectar conteúdo inadequado
                                </label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary w-100" onclick="processImages()">
                                <i class="bi bi-play-fill me-2"></i>
                                Processar Imagens
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h3 class="mb-4">Galeria de Imagens</h3>
    <div id="imageGrid" class="row g-4 mb-5"></div>

    <div class="card">
        <div class="card-body">
            <h3 class="card-title mb-4">Exemplos de Implementação</h3>
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#javascript">JavaScript</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#python">Python</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#php">PHP</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#curl">cURL</a>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="javascript">
                        <pre><code class="language-javascript">// Cliente JavaScript para IMG-STACK
async function processImage(imageUrl, options = {}) {
    const params = new URLSearchParams({
        image: imageUrl,
        r_w: options.width || 800,
        r_h: options.height || 600,
        i_f: options.format || 'webp',
        i_q: options.quality || 85
    });

    // Adicionar parâmetros de IA
    if (options.ai) {
        options.ai.forEach(feature => params.append('ai[]', feature));
    }

    const response = await fetch(`{{ env('API_GATEWAY_URL') }}?${params}`);
    const data = await response.json();

    if (data.status === 'ready') {
        return data;
    }

    // Aguardar processamento assíncrono
    const retryAfter = data.retry_after || 2;
    await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));

    return processImage(imageUrl, options);
}

// Exemplo de uso
const result = await processImage('https://example.com/image.jpg', {
    width: 1200,
    height: 800,
    format: 'webp',
    quality: 90,
    ai: ['faces', 'safe']
});

console.log('Imagem processada:', result.url);</code></pre>
                </div>
                <div class="tab-pane fade" id="python">
                        <pre><code class="language-python">import requests
import time

def process_image(image_url, **options):
    """Processa uma imagem usando a API IMG-STACK"""
    params = {
        'image': image_url,
        'r_w': options.get('width', 800),
        'r_h': options.get('height', 600),
        'i_f': options.get('format', 'webp'),
        'i_q': options.get('quality', 85)
    }

    # Adicionar parâmetros de IA
    ai_features = options.get('ai', [])
    for feature in ai_features:
        params[f'ai[]'] = feature

    response = requests.get('{{ env('API_GATEWAY_URL') }}', params=params)
    data = response.json()

    if data.get('status') == 'ready':
        return data

    # Aguardar processamento assíncrono
    retry_after = data.get('retry_after', 2)
    time.sleep(retry_after)

    return process_image(image_url, **options)

# Exemplo de uso
result = process_image(
    'https://example.com/image.jpg',
    width=1200,
    height=800,
    format='webp',
    quality=90,
    ai=['faces', 'safe']
)

print(f"Imagem processada: {result['url']}")</code></pre>
                </div>
                <div class="tab-pane fade" id="php">
                        <pre><code class="language-php">&lt;?php

function processImage($imageUrl, $options = []) {
    $params = [
        'image' => $imageUrl,
        'r_w' => $options['width'] ?? 800,
        'r_h' => $options['height'] ?? 600,
        'i_f' => $options['format'] ?? 'webp',
        'i_q' => $options['quality'] ?? 85
    ];

    // Adicionar parâmetros de IA
    if (!empty($options['ai'])) {
        foreach ($options['ai'] as $feature) {
            $params['ai[]'] = $feature;
        }
    }

    $url = '{{ env('API_GATEWAY_URL') }}?' . http_build_query($params);
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if ($data['status'] === 'ready') {
        return $data;
    }

    // Aguardar processamento assíncrono
    $retryAfter = $data['retry_after'] ?? 2;
    sleep($retryAfter);

    return processImage($imageUrl, $options);
}

// Exemplo de uso
$result = processImage('https://example.com/image.jpg', [
    'width' => 1200,
    'height' => 800,
    'format' => 'webp',
    'quality' => 90,
    'ai' => ['faces', 'safe']
]);

echo "Imagem processada: " . $result['url'];</code></pre>
                </div>
                <div class="tab-pane fade" id="curl">
                        <pre><code class="language-bash">#!/bin/bash

# Função para processar imagem
process_image() {
    local image_url="$1"
    local api_url="{{ env('API_GATEWAY_URL') }}"

    # Construir URL com parâmetros
    local params="image=${image_url}&r_w=1200&r_h=800&i_f=webp&i_q=90"
    params="${params}&ai[]=faces&ai[]=safe"

    echo "Processando imagem..."

    while true; do
        # Fazer requisição
        response=$(curl -s "${api_url}?${params}")
        status=$(echo "$response" | jq -r '.status')

        if [ "$status" = "ready" ]; then
            url=$(echo "$response" | jq -r '.url')
            echo "Imagem processada: $url"
            break
        fi

        # Aguardar antes de tentar novamente
        retry_after=$(echo "$response" | jq -r '.retry_after // 2')
        echo "Aguardando ${retry_after}s..."
        sleep "$retry_after"
    done
}

# Exemplo de uso
process_image "https://example.com/image.jpg"</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>

<script>
    // Configuração
    const config = {
        apiUrl: '{{ env('API_GATEWAY_URL') }}',
        images: [
            {url: 'https://images.pexels.com/photos/2662116/pexels-photo-2662116.jpeg', title: 'Paisagem Montanhosa'},
            {url: 'https://images.pexels.com/photos/1680140/pexels-photo-1680140.jpeg', title: 'Praia Tropical'},
            {url: 'https://images.pexels.com/photos/2253275/pexels-photo-2253275.jpeg', title: 'Floresta Verde'},
            {url: 'https://images.pexels.com/photos/1563356/pexels-photo-1563356.jpeg', title: 'Cidade Noturna'},
            {url: 'https://images.pexels.com/photos/2422915/pexels-photo-2422915.jpeg', title: 'Deserto Dourado'},
            {url: 'https://images.pexels.com/photos/1761279/pexels-photo-1761279.jpeg', title: 'Lago Sereno'}
        ]
    };

    // Função para criar card de imagem
    function createImageCard(imageData) {
        return `
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="image-wrapper skeleton">
                            <span class="status-badge badge bg-primary">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                                Processando
                            </span>
                            <img class="card-img-top" alt="${imageData.title}">
                        </div>
                        <div class="card-body skeleton-body">
                            <div class="skeleton-line rounded w-75"></div>
                            <div class="skeleton-line rounded w-50"></div>
                        </div>
                        <div class="card-body real-body d-none">
                            <h5 class="card-title fs-6">${imageData.title}</h5>
                            <p class="card-text text-muted small">
                                <span class="dimensions"></span> • <span class="format"></span>
                            </p>
                        </div>
                    </div>
                </div>
            `;
    }

    // Função para processar uma imagem
    async function processImage($card, imageData, params) {
        const $img = $card.find('img');
        const $imageWrapper = $card.find('.image-wrapper');
        const $statusBadge = $card.find('.status-badge');
        const $skeletonBody = $card.find('.skeleton-body');
        const $realBody = $card.find('.real-body');

        let retryCount = 0;
        const maxRetries = 20;
        let shouldStop = false;

        async function loadImage() {
            if (shouldStop) return;

            const queryParams = new URLSearchParams({
                image: imageData.url,
                r_w: params.r_w,
                r_h: params.r_h,
                i_f: params.i_f,
                i_q: params.i_q
            });

            // Adicionar parâmetros de IA corretamente
            if (params.ai && params.ai.length > 0) {
                params.ai.forEach(feature => {
                    queryParams.append('ai[]', feature);
                });
            }

            try {
                const response = await fetch(`${config.apiUrl}?${queryParams}`);
                const data = await response.json();

                // Verificar se há erro na resposta
                if (data.status === 'error' || data.error_code || data.errors) {
                    shouldStop = true;
                    throw new Error(data.message || 'Erro ao processar imagem');
                }

                if (data.status === 'ready' && data.url) {
                    // Exibir imagem
                    $img.on('load', function () {
                        $img.addClass('show');
                        setTimeout(() => {
                            $imageWrapper.removeClass('skeleton');
                            $statusBadge.remove();
                            $skeletonBody.addClass('d-none');
                            $realBody.removeClass('d-none');
                            $realBody.find('.dimensions').text(`${params.r_w}x${params.r_h}`);
                            $realBody.find('.format').text(params.i_f.toUpperCase());
                        }, 300);
                    });

                    $img.on('error', function () {
                        showError($imageWrapper, $statusBadge, $skeletonBody);
                    });

                    $img.attr('src', data.url);
                } else if (!shouldStop) {
                    // Atualizar status e tentar novamente
                    updateStatus($statusBadge, data.status);

                    if (retryCount < maxRetries) {
                        retryCount++;
                        const delay = (data.retry_after || 2) * 1000;
                        setTimeout(loadImage, delay);
                    } else {
                        showError($imageWrapper, $statusBadge, $skeletonBody);
                    }
                }
            } catch (error) {
                console.error('Erro:', error);
                showError($imageWrapper, $statusBadge, $skeletonBody);
            }
        }

        loadImage();
    }

    // Função para atualizar status
    function updateStatus($badge, status) {
        const statusMap = {
            'queued': {text: 'Na fila', class: 'bg-secondary'},
            'processing': {text: 'Processando', class: 'bg-primary'},
            'completed': {text: 'Finalizando', class: 'bg-success'},
            'failed': {text: 'Falhou', class: 'bg-danger'}
        };

        const config = statusMap[status] || statusMap['processing'];

        $badge
            .removeClass('bg-primary bg-secondary bg-success bg-danger')
            .addClass(config.class)
            .html(`
                    ${status !== 'failed' ? '<span class="spinner-border spinner-border-sm me-1"></span>' : '<i class="bi bi-x-circle me-1"></i>'}
                    ${config.text}
                `);
    }

    // Função para mostrar erro
    function showError($wrapper, $badge, $skeleton) {
        $wrapper.html(`
                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                    <div class="text-center">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                        <p class="mt-2 mb-0">Erro ao carregar</p>
                    </div>
                </div>
            `).removeClass('skeleton');
        $badge.remove();
        $skeleton.remove();
    }

    // Função principal para processar todas as imagens
    function processImages() {
        const $grid = $('#imageGrid');
        $grid.empty();

        // Obter parâmetros
        const params = {
            r_w: parseInt($('#width').val()),
            r_h: parseInt($('#height').val()),
            i_f: $('#format').val(),
            i_q: parseInt($('#quality').val())
        };

        // Adicionar parâmetros de IA
        const ai = [];
        if ($('#detectFaces').prop('checked')) ai.push('faces');
        if ($('#detectUnsafe').prop('checked')) ai.push('safe');
        if (ai.length > 0) {
            params.ai = ai;
        }

        // Processar cada imagem
        config.images.forEach((imageData, index) => {
            const $card = $(createImageCard(imageData));
            $grid.append($card);

            // Pequeno delay entre requisições
            setTimeout(() => {
                processImage($card, imageData, params);
            }, index * 200);
        });
    }

    // Adicionar definição do PHP para o Prism
    if (!Prism.languages.php) {
        Prism.languages.php = Prism.languages.extend('clike', {
            'keyword': /\b(?:__halt_compiler|abstract|and|array|as|break|callable|case|catch|class|clone|const|continue|declare|default|die|do|echo|else|elseif|empty|enddeclare|endfor|endforeach|endif|endswitch|endwhile|eval|exit|extends|final|finally|fn|for|foreach|function|global|goto|if|implements|include|include_once|instanceof|insteadof|interface|isset|list|match|namespace|new|or|parent|print|private|protected|public|require|require_once|return|self|static|switch|throw|trait|try|unset|use|var|while|xor|yield)\b/i,
            'boolean': {
                pattern: /\b(?:false|true)\b/i,
                alias: 'constant'
            },
            'constant': [/\b[A-Z_][A-Z0-9_]*\b/, /\b(?:null)\b/i],
            'comment': {
                pattern: /(^|[^\\])(?:\/\*[\s\S]*?\*\/|\/\/.*)/,
                lookbehind: true
            }
        });

        Prism.languages.insertBefore('php', 'string', {
            'shell-comment': {
                pattern: /(^|[^\\])#.*/,
                lookbehind: true,
                alias: 'comment'
            }
        });

        Prism.languages.insertBefore('php', 'comment', {
            'delimiter': {
                pattern: /\?>$|^<\?(?:php(?=\s)|=)?/i,
                alias: 'important'
            }
        });

        Prism.languages.insertBefore('php', 'keyword', {
            'variable': {
                pattern: /\$+(?:\w+\b|(?=\{))/i,
                inside: {
                    punctuation: /\$/
                }
            },
            'package': {
                pattern: /(namespace\s+|use\s+(?:function\s+|const\s+)?)[a-z_]\w*(?:\\[a-z_]\w*)*(?=\s*;)/i,
                lookbehind: true,
                inside: {
                    punctuation: /\\/
                }
            }
        });
    }

    // Inicializar
    $(document).ready(function () {
        Prism.highlightAll();
        processImages();
    });
</script>
</body>
</html>
