<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IMG-STACK - Demo de Processamento de Imagens Serverless</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --skeleton-gradient: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
        }

        .demo-container {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .image-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .image-wrapper {
            position: relative;
            height: 200px;
            background: #f8f9fa;
            overflow: hidden;
        }

        .image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .image-wrapper img.show {
            opacity: 1;
        }

        .skeleton {
            background: var(--skeleton-gradient);
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

        .status-badge {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            font-size: 0.75rem;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            border: 1px solid rgba(0, 0, 0, 0.1);
            padding: 0.375rem 0.75rem;
            border-radius: 2rem;
        }

        .status-badge.processing {
            background: rgba(102, 126, 234, 0.9);
            color: white;
            border-color: rgba(102, 126, 234, 0.3);
        }

        .control-panel {
            background: #f8f9fa;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .parameter-group {
            margin-bottom: 1rem;
        }

        .parameter-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-custom {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            transition: transform 0.2s ease;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            color: white;
        }

        .code-example {
            background: #2d2d2d;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin: 1rem 0;
            overflow-x: auto;
        }

        .feature-card {
            text-align: center;
            padding: 2rem;
            border-radius: 0.75rem;
            background: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .metric {
            font-size: 2.5rem;
            font-weight: 700;
            color: #667eea;
        }

        .metric-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tabs-container {
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        .nav-tabs {
            background: #f8f9fa;
            border: none;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 1rem 1.5rem;
            transition: color 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            background: white;
            color: #667eea;
            border-bottom: 2px solid #667eea;
        }

        .comparison-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .comparison-image {
            position: relative;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .comparison-label {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 600;
        }

        pre[class*="language-"] {
            margin: 0;
            border-radius: 0.5rem;
        }

        .alert-custom {
            background: rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(102, 126, 234, 0.3);
            color: #495057;
            border-radius: 0.5rem;
            padding: 1rem 1.5rem;
        }

        .upload-zone {
            border: 2px dashed #dee2e6;
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-zone:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        .upload-zone.dragover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body class="bg-light">
<div class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">IMG-STACK</h1>
                <p class="lead mb-4">Processamento inteligente de imagens em tempo real com arquitetura serverless.
                    Redimensione, otimize e analise imagens com IA sem se preocupar com infraestrutura.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-lightning-charge-fill me-2"></i>
                        <span>Processamento em tempo real</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-cpu-fill me-2"></i>
                        <span>100% Serverless</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-check-fill me-2"></i>
                        <span>Análise com IA</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 text-center d-none d-lg-block">
                <i class="bi bi-images" style="font-size: 8rem; opacity: 0.7;"></i>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="feature-card">
                <div class="metric" id="totalProcessed">0</div>
                <div class="metric-label">Imagens Processadas</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="feature-card">
                <div class="metric" id="avgProcessingTime">0s</div>
                <div class="metric-label">Tempo Médio</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="feature-card">
                <div class="metric" id="cacheHitRate">0%</div>
                <div class="metric-label">Taxa de Cache</div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="feature-card">
                <div class="metric" id="bandwidthSaved">0MB</div>
                <div class="metric-label">Banda Economizada</div>
            </div>
        </div>
    </div>

    <div class="demo-container">
        <h2 class="h3 mb-4">
            <i class="bi bi-play-circle-fill me-2 text-primary"></i>
            Demonstração Interativa
        </h2>

        <div class="alert alert-custom mb-4">
            <i class="bi bi-info-circle-fill me-2"></i>
            <strong>Cenário Real:</strong> Sistema de e-commerce processando imagens de produtos para diferentes
            dispositivos e contextos.
        </div>

        <div class="control-panel">
            <h5 class="mb-3">Parâmetros de Processamento</h5>
            <div class="row">
                <div class="col-md-3">
                    <div class="parameter-group">
                        <div class="parameter-label">Largura (px)</div>
                        <input type="number" class="form-control" id="widthInput" value="800" min="100" max="2000">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="parameter-group">
                        <div class="parameter-label">Altura (px)</div>
                        <input type="number" class="form-control" id="heightInput" value="600" min="100" max="2000">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="parameter-group">
                        <div class="parameter-label">Formato</div>
                        <select class="form-select" id="formatSelect">
                            <option value="webp">WebP (Recomendado)</option>
                            <option value="jpg">JPEG</option>
                            <option value="png">PNG</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="parameter-group">
                        <div class="parameter-label">Qualidade</div>
                        <input type="range" class="form-range" id="qualityRange" min="60" max="100" value="85">
                        <small class="text-muted">Qualidade: <span id="qualityValue">85</span>%</small>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="parameter-group">
                        <div class="parameter-label">Análise com IA</div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="faceDetection" value="faces" checked>
                            <label class="form-check-label" for="faceDetection">
                                <i class="bi bi-person-bounding-box me-1"></i>
                                Detecção de Rostos
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="safeContent" value="safe" checked>
                            <label class="form-check-label" for="safeContent">
                                <i class="bi bi-shield-fill-check me-1"></i>
                                Conteúdo Seguro
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-custom" onclick="processAllImages()">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Processar Todas
                    </button>
                </div>
            </div>
        </div>

        <h5 class="mb-3 mt-4">Casos de Uso</h5>
        <div class="tabs-container">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#ecommerce">E-commerce</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#social">Rede Social</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#safety">Teste de Segurança</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#custom">URL Personalizada</a>
                </li>
            </ul>
            <div class="tab-content bg-white p-4">
                <div class="tab-pane fade show active" id="ecommerce">
                    <p class="text-muted mb-3">Produtos de e-commerce otimizados para diferentes dispositivos</p>
                    <div id="ecommerceGrid" class="image-grid"></div>
                </div>
                <div class="tab-pane fade" id="social">
                    <p class="text-muted mb-3">Fotos de perfil com detecção de rostos</p>
                    <div id="socialGrid" class="image-grid"></div>
                </div>
                <div class="tab-pane fade" id="safety">
                    <div class="alert alert-warning mb-3">
                        <i class="bi bi-shield-exclamation me-2"></i>
                        <strong>Teste de Moderação:</strong> Estas imagens demonstram o sistema de detecção de conteúdo
                        potencialmente inadequado.
                        Com a opção "Conteúdo Seguro" ativada, imagens detectadas serão automaticamente desfocadas.
                    </div>
                    <div id="safetyGrid" class="image-grid"></div>
                </div>
                <div class="tab-pane fade" id="custom">
                    <div class="input-group mb-3">
                            <span class="input-group-text">
                                <i class="bi bi-link-45deg"></i>
                            </span>
                        <input type="text" class="form-control" id="customUrl"
                               placeholder="https://exemplo.com/imagem.jpg"
                               value="https://images.pexels.com/photos/1108099/pexels-photo-1108099.jpeg">
                        <button class="btn btn-custom" onclick="processCustomUrl()">
                            <i class="bi bi-cloud-download me-2"></i>
                            Processar
                        </button>
                    </div>
                    <small class="text-muted d-block mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Cole a URL de qualquer imagem pública (JPG, PNG, WebP) para processar. Limite recomendado: 5MB.
                    </small>
                    <div id="customGrid" class="image-grid"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="demo-container">
        <h2 class="h3 mb-4">
            <i class="bi bi-code-slash me-2 text-primary"></i>
            Exemplos de Implementação
        </h2>

        <div class="tabs-container">
            <ul class="nav nav-tabs" role="tablist">
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
            <div class="tab-content bg-white p-4">
                <div class="tab-pane fade show active" id="javascript">
                        <pre><code class="language-javascript">class ImageProcessor {
    constructor(apiUrl) {
        this.apiUrl = apiUrl;
        this.cache = new Map();
    }

    async processImage(imageUrl, options = {}) {
        const params = new URLSearchParams({
            image: imageUrl,
            r_w: options.width || 800,
            r_h: options.height || 600,
            i_f: options.format || 'webp',
            i_q: options.quality || 85,
            ai: options.ai || ['faces', 'safe']
        });

        const cacheKey = params.toString();
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const response = await fetch(`${this.apiUrl}?${params}`);
            const data = await response.json();

            if (data.status === 'ready' && data.url) {
                this.cache.set(cacheKey, data);
                return data;
            }

            // Processamento assíncrono - implementar polling
            return await this.pollForResult(params, data.retry_after || 2);
        } catch (error) {
            console.error('Erro no processamento:', error);
            throw error;
        }
    }

    async pollForResult(params, retryAfter) {
        const maxAttempts = 20;
        let attempts = 0;

        while (attempts < maxAttempts) {
            await this.sleep(retryAfter * 1000);

            const response = await fetch(`${this.apiUrl}?${params}`);
            const data = await response.json();

            if (data.status === 'ready' && data.url) {
                return data;
            }

            attempts++;
            retryAfter = Math.min(retryAfter * 1.2, 5);
        }

        throw new Error('Timeout no processamento da imagem');
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// Uso
const processor = new ImageProcessor('{{ env('API_GATEWAY_URL') }}');
const result = await processor.processImage('https://example.com/produto.jpg', {
    width: 1200,
    height: 900,
    format: 'webp',
    quality: 90,
    ai: ['faces', 'safe']
});</code></pre>
                </div>
                <div class="tab-pane fade" id="python">
                        <pre><code class="language-python">import requests
import time
from typing import Dict, List, Optional
from urllib.parse import urlencode

class ImageProcessor:
    def __init__(self, api_url: str):
        self.api_url = api_url
        self.session = requests.Session()
        self.cache = {}

    def process_image(self, image_url: str, **options) -> Dict:
        params = {
            'image': image_url,
            'r_w': options.get('width', 800),
            'r_h': options.get('height', 600),
            'i_f': options.get('format', 'webp'),
            'i_q': options.get('quality', 85),
            'ai': options.get('ai', ['faces', 'safe'])
        }

        cache_key = urlencode(params, doseq=True)
        if cache_key in self.cache:
            return self.cache[cache_key]

        try:
            response = self.session.get(self.api_url, params=params)
            data = response.json()

            if data.get('status') == 'ready' and data.get('url'):
                self.cache[cache_key] = data
                return data

            # Processamento assíncrono
            return self._poll_for_result(params, data.get('retry_after', 2))

        except requests.RequestException as e:
            print(f'Erro no processamento: {e}')
            raise

    def _poll_for_result(self, params: Dict, retry_after: float) -> Dict:
        max_attempts = 20
        attempts = 0

        while attempts < max_attempts:
            time.sleep(retry_after)

            response = self.session.get(self.api_url, params=params)
            data = response.json()

            if data.get('status') == 'ready' and data.get('url'):
                return data

            attempts += 1
            retry_after = min(retry_after * 1.2, 5)

        raise TimeoutError('Timeout no processamento da imagem')

# Uso
processor = ImageProcessor('{{ env('API_GATEWAY_URL') }}')
result = processor.process_image(
    'https://example.com/produto.jpg',
    width=1200,
    height=900,
    format='webp',
    quality=90,
    ai=['faces', 'safe']
)</code></pre>
                </div>
                <div class="tab-pane fade" id="php">
                        <pre><code class="language-php">&lt;?php

class ImageProcessor {
    private string $apiUrl;
    private array $cache = [];

    public function __construct(string $apiUrl) {
        $this->apiUrl = $apiUrl;
    }

    public function processImage(string $imageUrl, array $options = []): array {
        $params = [
            'image' => $imageUrl,
            'r_w' => $options['width'] ?? 800,
            'r_h' => $options['height'] ?? 600,
            'i_f' => $options['format'] ?? 'webp',
            'i_q' => $options['quality'] ?? 85,
            'ai' => $options['ai'] ?? ['faces', 'safe']
        ];

        $cacheKey = http_build_query($params);
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            $url = $this->apiUrl . '?' . http_build_query($params);
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if ($data['status'] === 'ready' && isset($data['url'])) {
                $this->cache[$cacheKey] = $data;
                return $data;
            }

            // Processamento assíncrono
            return $this->pollForResult($params, $data['retry_after'] ?? 2);

        } catch (Exception $e) {
            throw new RuntimeException('Erro no processamento: ' . $e->getMessage());
        }
    }

    private function pollForResult(array $params, float $retryAfter): array {
        $maxAttempts = 20;
        $attempts = 0;

        while ($attempts < $maxAttempts) {
            sleep((int)$retryAfter);

            $url = $this->apiUrl . '?' . http_build_query($params);
            $response = file_get_contents($url);
            $data = json_decode($response, true);

            if ($data['status'] === 'ready' && isset($data['url'])) {
                return $data;
            }

            $attempts++;
            $retryAfter = min($retryAfter * 1.2, 5);
        }

        throw new RuntimeException('Timeout no processamento da imagem');
    }
}

// Uso
$processor = new ImageProcessor('{{ env('API_GATEWAY_URL') }}');
$result = $processor->processImage('https://example.com/produto.jpg', [
    'width' => 1200,
    'height' => 900,
    'format' => 'webp',
    'quality' => 90,
    'ai' => ['faces', 'safe']
]);</code></pre>
                </div>
                <div class="tab-pane fade" id="curl">
                        <pre><code class="language-bash"># Requisição inicial
curl -X GET "{{ env('API_GATEWAY_URL') }}?image=https://example.com/produto.jpg&r_w=1200&r_h=900&i_f=webp&i_q=90&ai=faces,safe"

# Resposta para processamento assíncrono
{
  "status": "processing",
  "retry_after": 2,
  "job_id": "abc123"
}

# Script para polling
#!/bin/bash
API_URL="{{ env('API_GATEWAY_URL') }}"
IMAGE_URL="https://example.com/produto.jpg"
MAX_ATTEMPTS=20
ATTEMPT=0

while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    RESPONSE=$(curl -s "$API_URL?image=$IMAGE_URL&r_w=1200&r_h=900&i_f=webp&i_q=90&ai=faces,safe")
    STATUS=$(echo $RESPONSE | jq -r '.status')

    if [ "$STATUS" = "ready" ]; then
        URL=$(echo $RESPONSE | jq -r '.url')
        echo "Imagem processada: $URL"
        break
    fi

    RETRY_AFTER=$(echo $RESPONSE | jq -r '.retry_after // 2')
    echo "Status: $STATUS - Tentando novamente em ${RETRY_AFTER}s..."
    sleep $RETRY_AFTER

    ATTEMPT=$((ATTEMPT + 1))
done</code></pre>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 mb-3">
            <div class="feature-card">
                <i class="bi bi-speedometer2 feature-icon"></i>
                <h4>Performance Otimizada</h4>
                <p class="text-muted">Processamento paralelo com Lambda, cache inteligente no CloudFront e decisão
                    automática entre sync/async.</p>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="feature-card">
                <i class="bi bi-shield-lock-fill feature-icon"></i>
                <h4>Segurança Integrada</h4>
                <p class="text-muted">URLs assinadas, análise de conteúdo com AWS Rekognition e proteção contra conteúdo
                    inadequado.</p>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="feature-card">
                <i class="bi bi-graph-up-arrow feature-icon"></i>
                <h4>Escalabilidade Infinita</h4>
                <p class="text-muted">Arquitetura 100% serverless que escala automaticamente com a demanda, sem
                    limites.</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-markup-templating.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-bash.min.js"></script>

<script>
    const config = {
        apiUrl: '{{ env('API_GATEWAY_URL') }}',
        ecommerceImages: [
            {
                url: 'https://images.unsplash.com/photo-1523275335684-37898b6baf30',
                title: 'Relógio Premium',
                category: 'produto'
            },
            {
                url: 'https://images.unsplash.com/photo-1572635196237-14b3f281503f',
                title: 'Óculos de Sol',
                category: 'produto'
            },
            {
                url: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e',
                title: 'Headphone',
                category: 'produto'
            },
            {
                url: 'https://images.unsplash.com/photo-1542291026-7eec264c27ff',
                title: 'Tênis Nike',
                category: 'produto'
            },
            {
                url: 'https://images.unsplash.com/photo-1484704849700-f032a568e944',
                title: 'Fone de Ouvido',
                category: 'produto'
            },
            {
                url: 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f',
                title: 'Câmera Vintage',
                category: 'produto'
            }
        ],
        socialImages: [
            {
                url: 'https://images.pexels.com/photos/3768125/pexels-photo-3768125.jpeg',
                title: 'Perfil Jéssica',
                category: 'perfil'
            },
            {
                url: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80',
                title: 'Perfil Maria',
                category: 'perfil'
            },
            {
                url: 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e',
                title: 'Perfil Carlos',
                category: 'perfil'
            }
        ],
        unsafeTestImages: [
            {
                url: 'https://images.unsplash.com/photo-1595590424283-b8f17842773f',
                title: 'Arma de Fogo',
                category: 'teste'
            },
            {
                url: 'https://www.nationaldefensemagazine.org/-/media/sites/magazine/2021/10/1673223.jpg',
                title: 'Militares Armados',
                category: 'teste'
            },
            {
                url: 'https://img.freepik.com/fotos-gratis/linda-mulher-de-biquini-branco-garota-jovem-e-esportiva-posando-em-uma-praia-no-verao_231208-7765.jpg',
                title: 'Mulher de Bikini',
                category: 'teste'
            }
        ],
        customImages: []
    };

    let stats = {
        totalProcessed: 0,
        totalTime: 0,
        cacheHits: 0,
        bandwidthSaved: 0
    };

    $.fn.imgStackProcess = function (imageData, params) {
        return this.each(function () {
            const $card = $(this);
            const startTime = Date.now();
            let retryCount = 0;
            const maxRetries = 20;

            const cardHtml = `
                    <div class="image-card">
                        <div class="image-wrapper skeleton">
                            <span class="status-badge processing">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                                Processando
                            </span>
                            <img alt="${imageData.title}">
                        </div>
                        <div class="p-3">
                            <h6 class="mb-1">${imageData.title}</h6>
                            <small class="text-muted d-block">
                                ${params.r_w}x${params.r_h} • ${params.i_f.toUpperCase()} • ${params.i_q}%
                            </small>
                            <small class="text-muted d-block">
                                <span class="process-time"></span>
                            </small>
                        </div>
                    </div>
                `;

            $card.html(cardHtml);

            const $img = $card.find('img');
            const $wrapper = $card.find('.image-wrapper');
            const $badge = $card.find('.status-badge');
            const $processTime = $card.find('.process-time');

            async function loadImage() {
                const queryParams = $.param({
                    ...params,
                    image: imageData.url
                });

                try {
                    const response = await $.getJSON(`${config.apiUrl}?${queryParams}`);

                    if (response.status === 'ready' && response.url) {
                        displayImage(response.url);
                        updateStats(response.cached);
                    } else {
                        updateStatus(response.status);
                        const delay = (response.retry_after || 2) * 1000;
                        scheduleRetry(delay);
                    }
                } catch (error) {
                    console.error('Erro:', error);
                    showError();
                }
            }

            function displayImage(url) {
                $img.on('load', function () {
                    const processTime = ((Date.now() - startTime) / 1000).toFixed(2);
                    $processTime.text(`Processado em ${processTime}s`);

                    $img.addClass('show');
                    $wrapper.removeClass('skeleton');
                    $badge.fadeOut();

                    stats.totalProcessed++;
                    stats.totalTime += parseFloat(processTime);
                    updateDashboard();
                });

                $img.attr('src', url);
            }

            function updateStatus(status) {
                const statusMap = {
                    'queued': 'Na fila',
                    'processing': 'Processando',
                    'completed': 'Finalizando'
                };

                $badge.html(`
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        ${statusMap[status] || 'Processando'}
                    `);
            }

            function scheduleRetry(delay) {
                if (retryCount >= maxRetries) {
                    showError();
                    return;
                }
                retryCount++;
                setTimeout(loadImage, delay);
            }

            function showError() {
                $wrapper.html(`
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                            <div class="text-center">
                                <i class="bi bi-exclamation-triangle fs-1"></i>
                                <p class="mt-2 mb-0">Erro ao processar</p>
                            </div>
                        </div>
                    `).removeClass('skeleton');
                $badge.remove();
            }

            function updateStats(cached) {
                if (cached) {
                    stats.cacheHits++;
                    stats.bandwidthSaved += estimateFileSize(params.r_w, params.r_h, params.i_f);
                }
            }

            loadImage();
        });
    };

    function estimateFileSize(width, height, format) {
        const pixels = width * height;
        const bytesPerPixel = format === 'webp' ? 0.5 : format === 'jpg' ? 0.8 : 1.2;
        return (pixels * bytesPerPixel) / (1024 * 1024);
    }

    function updateDashboard() {
        $('#totalProcessed').text(stats.totalProcessed);
        $('#avgProcessingTime').text(stats.totalProcessed > 0 ?
            `${(stats.totalTime / stats.totalProcessed).toFixed(1)}s` : '0s');
        $('#cacheHitRate').text(stats.totalProcessed > 0 ?
            `${Math.round((stats.cacheHits / stats.totalProcessed) * 100)}%` : '0%');
        $('#bandwidthSaved').text(`${stats.bandwidthSaved.toFixed(1)}MB`);
    }

    function getParams() {
        const ai = [];
        if ($('#faceDetection').is(':checked')) ai.push('faces');
        if ($('#safeContent').is(':checked')) ai.push('safe');

        return {
            r_w: parseInt($('#widthInput').val()),
            r_h: parseInt($('#heightInput').val()),
            i_f: $('#formatSelect').val(),
            i_q: parseInt($('#qualityRange').val()),
            ai: ai
        };
    }

    window.processAllImages = function () {
        $('.image-grid').empty();
        stats = {totalProcessed: 0, totalTime: 0, cacheHits: 0, bandwidthSaved: 0};
        updateDashboard();

        loadImages('ecommerce');
        loadImages('social');
        loadImages('safety');
        if (config.customImages.length > 0) {
            loadImages('custom');
        }
    }

    function loadImages(type) {
        const $grid = $(`#${type}Grid`);
        $grid.empty();

        const images = type === 'ecommerce' ? config.ecommerceImages :
            type === 'social' ? config.socialImages :
                type === 'safety' ? config.unsafeTestImages :
                    config.customImages;

        images.forEach(imageData => {
            const $col = $('<div>');
            $grid.append($col);
            $col.imgStackProcess(imageData, getParams());
        });
    }

    window.processCustomUrl = function () {
        const url = $('#customUrl').val().trim();

        if (!url) {
            alert('Por favor, insira uma URL válida de imagem.');
            return;
        }

        if (!url.match(/^https?:\/\/.+\.(jpg|jpeg|png|webp|gif)$/i)) {
            alert('A URL deve apontar para uma imagem válida (JPG, PNG, WebP ou GIF).');
            return;
        }

        config.customImages = [{
            url: url,
            title: 'Imagem Personalizada'
        }];

        loadImages('custom');
    }

    $('#qualityRange').on('input', function () {
        $('#qualityValue').text(this.value);
    });

    $('#customUrl').on('keypress', function (e) {
        if (e.which === 13) {
            processCustomUrl();
        }
    });

    $(document).ready(function () {
        processAllImages();
    });
</script>
</body>
</html>
