<?php

namespace App\Http\Services;

class SkeletonService
{
    /**
     * Gera skeleton SVG inteligente baseado nas dimensões
     *
     * @param int $width
     * @param int $height
     * @param string $style
     * @return string
     */
    public function generateSkeleton(int $width, int $height, string $style = 'auto'): string
    {
        $detectedStyle = $this->detectSkeletonStyle($width, $height, $style);
        $checkUrl = $this->buildCheckUrl();

        return $this->createAnimatedSkeleton($width, $height, $detectedStyle, $checkUrl);
    }

    /**
     * Detecta automaticamente o melhor estilo de skeleton
     *
     * @param int width
     * @param int height
     * @param string $requestedStyle
     * @return string
     */
    private function detectSkeletonStyle(int $width, int $height, string $requestedStyle): string
    {
        if ($requestedStyle !== 'auto') {
            return $requestedStyle;
        }

        $aspectRatio = $width / $height;

        // Quadrado ou quase quadrado = Avatar
        if (abs($aspectRatio - 1) < 0.3) {
            return 'avatar';
        }

        // Muito horizontal = Banner
        if ($aspectRatio > 2.5) {
            return 'banner';
        }

        // Muito vertical = Card
        if ($aspectRatio < 0.6) {
            return 'card';
        }

        // Padrão = Product
        return 'product';
    }

    /**
     * Constrói URL para verificar se imagem está pronta
     *
     * @return string
     */
    private function buildCheckUrl(): string
    {
        $currentUrl = request()->fullUrl();
        if (strpos($currentUrl, 'skeleton=') !== false) {
            return preg_replace('/skeleton=[^&]*/', 'skeleton=off', $currentUrl);
        }

        return $currentUrl . '&skeleton=off';
    }

    /**
     * Cria SVG animado com auto-substituição
     *
     * @param int $width
     * @param int $height
     * @param string $style
     * @param string $checkUrl
     * @return string
     */
    private function createAnimatedSkeleton(int $width, int $height, string $style, string $checkUrl): string
    {
        $skeletonContent = $this->getSkeletonByStyle($style, $width, $height);
        $pollingScript = $this->getPollingScript($checkUrl, $width, $height);

        return <<<SVG
<svg width="{$width}" height="{$height}" xmlns="http://www.w3.org/2000/svg" style="background:#fafafa;">
    <defs>
        <!-- Animação shimmer -->
        <linearGradient id="shimmer-{$width}-{$height}" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" style="stop-color:#f0f0f0;stop-opacity:1"/>
            <stop offset="50%" style="stop-color:#e8e8e8;stop-opacity:1"/>
            <stop offset="100%" style="stop-color:#f0f0f0;stop-opacity:1"/>
            <animateTransform
                attributeName="gradientTransform"
                type="translate"
                values="-{$width} 0;{$width} 0;-{$width} 0"
                dur="2s"
                repeatCount="indefinite"/>
        </linearGradient>

        <!-- Gradiente para fade -->
        <linearGradient id="fade-{$width}-{$height}" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" style="stop-color:#f8f9fa;stop-opacity:0.8"/>
            <stop offset="100%" style="stop-color:#e9ecef;stop-opacity:0.3"/>
        </linearGradient>
    </defs>

    {$skeletonContent}

    <!-- Indicador de loading -->
    <g id="loading-indicator">
        <rect x="10" y="10" width="80" height="20" fill="rgba(13,110,253,0.1)" rx="10"/>
        <text x="50" y="23" text-anchor="middle" font-size="11" fill="#0d6efd" font-family="system-ui">
            Otimizando...
        </text>
        <!-- Spinner animado -->
        <circle cx="25" cy="20" r="6" fill="none" stroke="#0d6efd" stroke-width="1.5" opacity="0.3"/>
        <circle cx="25" cy="20" r="6" fill="none" stroke="#0d6efd" stroke-width="1.5"
                stroke-dasharray="12 8" stroke-linecap="round">
            <animateTransform attributeName="transform" type="rotate"
                values="0 25 20;360 25 20" dur="1s" repeatCount="indefinite"/>
        </circle>
    </g>

    {$pollingScript}
</svg>
SVG;
    }

    /**
     * Retorna conteúdo do skeleton baseado no estilo
     *
     * @param string $style
     * @param int $width
     * @param int $height
     * @return string
     */
    private function getSkeletonByStyle(string $style, int $width, int $height): string
    {
        $shimmerFill = "url(#shimmer-{$width}-{$height})";
        $staticFill = "#e9ecef";

        return match ($style) {
            'avatar' => $this->avatarSkeleton($width, $height, $shimmerFill, $staticFill),
            'banner' => $this->bannerSkeleton($width, $height, $shimmerFill, $staticFill),
            'card' => $this->cardSkeleton($width, $height, $shimmerFill, $staticFill),
            'product' => $this->productSkeleton($width, $height, $shimmerFill, $staticFill),
            default => $this->productSkeleton($width, $height, $shimmerFill, $staticFill)
        };
    }

    private function productSkeleton(int $width, int $height, string $shimmer, string $static): string
    {
        $imageHeight = $height * 0.65;
        $titleY = $imageHeight + 15;
        $priceY = $titleY + 20;

        return <<<HTML
        <!-- Área da imagem principal -->
        <rect width="{$width}" height="{$imageHeight}" fill="{$shimmer}" rx="8"/>

        <!-- Título do produto -->
        <rect y="{$titleY}" width="70%" height="12" fill="{$static}" rx="6"/>

        <!-- Preço -->
        <rect y="{$priceY}" width="45%" height="10" fill="{$static}" rx="5"/>

        <!-- Rating stars (opcional) -->
        <rect y="{$priceY}" x="55%" width="35%" height="8" fill="{$static}" rx="4"/>
HTML;
    }

    private function avatarSkeleton(int $width, int $height, string $shimmer, string $static): string
    {
        $centerX = $width / 2;
        $centerY = $height * 0.4;
        $radius = min($width, $height) * 0.25;
        $nameY = $centerY + $radius + 20;

        return <<<HTML
        <!-- Avatar circular -->
        <circle cx="{$centerX}" cy="{$centerY}" r="{$radius}" fill="{$shimmer}"/>

        <!-- Nome -->
        <rect x="25%" y="{$nameY}" width="50%" height="10" fill="{$static}" rx="5"/>

        <!-- Subtítulo -->
        <rect x="30%" y="{$nameY}" dy="18" width="40%" height="8" fill="{$static}" rx="4"/>
HTML;
    }

    private function bannerSkeleton(int $width, int $height, string $shimmer, string $static): string
    {
        return <<<HTML
        <!-- Fundo do banner -->
        <rect width="{$width}" height="{$height}" fill="{$shimmer}" rx="12"/>

        <!-- Texto principal -->
        <rect x="5%" y="30%" width="40%" height="15" fill="{$static}" rx="7"/>

        <!-- Texto secundário -->
        <rect x="5%" y="55%" width="25%" height="10" fill="{$static}" rx="5"/>
HTML;
    }

    private function cardSkeleton(int $width, int $height, string $shimmer, string $static): string
    {
        $imageHeight = $height * 0.5;
        $contentStart = $imageHeight + 15;

        return <<<HTML
        <!-- Imagem do card -->
        <rect width="{$width}" height="{$imageHeight}" fill="{$shimmer}" rx="8"/>

        <!-- Título -->
        <rect x="8%" y="{$contentStart}" width="84%" height="12" fill="{$static}" rx="6"/>

        <!-- Descrição linha 1 -->
        <rect x="8%" y="{$contentStart}" dy="25" width="75%" height="8" fill="{$static}" rx="4"/>

        <!-- Descrição linha 2 -->
        <rect x="8%" y="{$contentStart}" dy="40" width="60%" height="8" fill="{$static}" rx="4"/>
HTML;
    }

    /**
     * JavaScript para polling e substituição automática
     *
     * @param string $checkUrl
     * @param int $width
     * @param int $height
     * @return string
     */
    private function getPollingScript(string $checkUrl, int $width, int $height): string
    {
        return <<<SCRIPT
<script type="text/javascript">
<![CDATA[
(function() {
    const svg = document.currentScript.closest('svg');
    if (!svg) {
        console.error('SVG element not found');
        return;
    }

    const loadingIndicator = svg.querySelector('#loading-indicator');
    let attempts = 0;
    let isChecking = false;

    function updateLoadingText(text, color = '#0d6efd') {
        if (!loadingIndicator) return;

        const textEl = loadingIndicator.querySelector('text');
        if (textEl) {
            textEl.textContent = text;
            textEl.setAttribute('fill', color);
        }
    }

    function hideLoadingIndicator() {
        if (loadingIndicator) {
            loadingIndicator.style.opacity = '0';
            loadingIndicator.style.transition = 'opacity 0.5s ease';
        }
    }

    function replaceWithImage(imageSrc) {
        try {
            // Tentativa múltipla de criar o elemento img
            let img = null;


            if (!img || typeof img.style === 'undefined') {
                try {
                    img = new Image();
                } catch (e) {
                    console.warn('Method 3 failed:', e);
                }
            }

            // Se ainda assim falhou, desiste
            if (!img || typeof img.style === 'undefined') {
                console.error('All methods to create img element failed');
                updateLoadingText('Erro ao carregar', '#dc3545');
                return;
            }

            // Configurar propriedades da imagem com verificações
            try {
                img.src = imageSrc;

                if (img.style) {
                    img.style.width = '{$width}px';
                    img.style.height = '{$height}px';
                    img.style.opacity = '0';
                    img.style.transition = 'opacity 0.8s ease-in-out';
                }

            } catch (error) {
                console.error('Error setting image properties:', error);
            }

            img.onload = function() {
                try {
                    // Fade in da imagem real
                    if (this.style) {
                        this.style.opacity = '1';
                    }

                    // Fade out do skeleton
                    if (svg && svg.style) {
                        svg.style.transition = 'opacity 0.8s ease-in-out';
                        svg.style.opacity = '0';
                    }

                    setTimeout(() => {
                        try {
                            if (svg && svg.parentNode && typeof svg.parentNode.replaceChild === 'function') {
                                svg.parentNode.replaceChild(img, svg);
                            }
                        } catch (replaceError) {
                            console.error('Error replacing element:', replaceError);
                            // Fallback: inserir após o SVG e remover o SVG
                            try {
                                if (svg && svg.parentNode) {
                                    svg.parentNode.insertBefore(img, svg.nextSibling);
                                    svg.remove();
                                }
                            } catch (fallbackError) {
                                console.error('Fallback replacement failed:', fallbackError);
                            }
                        }
                    }, 800);
                } catch (error) {
                    console.error('Error in image onload:', error);
                    setTimeout(() => checkImage(), 2000); // Tenta novamente após delay
                }
            };

            img.onerror = function() {
                console.log('Image load failed, retrying...');
                setTimeout(() => checkImage(), 1000); // Delay antes de tentar novamente
            };

        } catch (error) {
            console.error('Critical error in replaceWithImage:', error);
            updateLoadingText('Erro crítico', '#dc3545');
        }
    }

    function checkImage() {
        if (isChecking || attempts >= 25) {
            if (attempts >= 25) {
                updateLoadingText('Erro ao processar', '#dc3545');
                setTimeout(hideLoadingIndicator, 2000);
            }
            return;
        }

        isChecking = true;
        attempts++;

        // Atualiza texto baseado no progresso
        if (attempts <= 3) {
            updateLoadingText('Iniciando...');
        } else if (attempts <= 8) {
            updateLoadingText('Processando...');
        } else if (attempts <= 15) {
            updateLoadingText('Finalizando...');
        } else {
            updateLoadingText('Quase pronto...');
        }

        const testImg = new Image();

        testImg.onload = function() {
            isChecking = false;
            updateLoadingText('Concluído!', '#198754');
            setTimeout(() => replaceWithImage(this.src), 500);
        };

        testImg.onerror = function() {
            isChecking = false;
            const nextCheck = Math.min(2000 + (attempts * 200), 4000);
            setTimeout(checkImage, nextCheck);
        };

        // Adiciona timestamp para evitar cache
        const checkUrl = '{$checkUrl}' + ('{$checkUrl}'.includes('?') ? '&' : '?') + 't=' + Date.now();
        testImg.src = checkUrl;
    }

    // Inicia verificação após 1.5s
    setTimeout(checkImage, 1500);

})();
]]>
</script>
SCRIPT;
    }
}
