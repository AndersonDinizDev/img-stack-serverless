<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Processamento de Imagens</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* Apenas CSS customizado essencial */
        .image-wrapper {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .image-wrapper img {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .image-wrapper img.show {
            opacity: 1;
        }

        /* Skeleton Animation */
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
            animation: shimmer 1.5s infinite;
            background: linear-gradient(90deg, #e0e0e0 25%, #d0d0d0 50%, #e0e0e0 75%);
            background-size: 200% 100%;
        }

        /* Status Badge */
        .status-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="mb-4">Galeria de Imagens - Processamento Assíncrono</h1>

    <div id="imageGrid" class="row g-4">
        <!-- Cards serão inseridos aqui -->
    </div>
</div>

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Configuração
    const config = {
        apiUrl: 'https://dzy9qlgb7cwht.cloudfront.net/api/v1/image',
        images: [
            {url: 'https://images.pexels.com/photos/2662116/pexels-photo-2662116.jpeg', title: 'Paisagem 1'},
            {
                url: 'https://media.imperatriz.ma.gov.br/svVaYVi2TBR-sEZaFveY8tJ4wzQ=/750x0/novo.imperatriz.ma.gov.br/media/site/content/article/WhatsApp_Image_2023-12-01_at_08.59.29.jpeg',
                title: 'Paisagem 2'
            },
            {url: 'https://img.cdndsgni.com/preview/10210688.jpg', title: 'Paisagem 3'},
            {url: 'https://images.pexels.com/photos/1680140/pexels-photo-1680140.jpeg', title: 'Paisagem 4'},
            {url: 'https://images.pexels.com/photos/2253275/pexels-photo-2253275.jpeg', title: 'Paisagem 5'},
            {url: 'https://images.pexels.com/photos/1563356/pexels-photo-1563356.jpeg', title: 'Paisagem 6'}
        ],
        params: {
            r_w: 1920,
            r_h: 1080,
            i_f: 'webp',
            i_q: 100
        }
    };

    // Plugin jQuery para imagem assíncrona
    $.fn.asyncImage = function (imageData, params) {
        return this.each(function () {
            const $card = $(this);
            let retryCount = 0;
            const maxRetries = 20;

            // Template do card com skeleton
            const cardHtml = `
                    <div class="card h-100 shadow-sm">
                        <div class="image-wrapper bg-secondary skeleton">
                            <span class="status-badge badge bg-primary d-none">
                                <span class="spinner-border spinner-border-sm me-1"></span>
                                Processando
                            </span>
                            <img class="card-img-top w-100 h-100" alt="${imageData.title}">
                        </div>
                        <div class="card-body skeleton-body">
                            <div class="skeleton-line rounded mb-2 w-75"></div>
                            <div class="skeleton-line rounded w-50"></div>
                        </div>
                        <div class="card-body real-body d-none">
                            <h5 class="card-title fs-6">${imageData.title}</h5>
                            <p class="card-text text-muted small">
                                ${params.r_w}x${params.r_h} • ${params.i_f.toUpperCase()}
                            </p>
                        </div>
                    </div>
                `;

            $card.html(cardHtml);

            // Elementos jQuery
            const $img = $card.find('img');
            const $imageWrapper = $card.find('.image-wrapper');
            const $statusBadge = $card.find('.status-badge');
            const $skeletonBody = $card.find('.skeleton-body');
            const $realBody = $card.find('.real-body');

            // Função para carregar imagem
            async function loadImage() {
                const queryParams = $.param({
                    ...params,
                    image: imageData.url
                });

                try {
                    const data = await $.getJSON(`${config.apiUrl}?${queryParams}`);

                    if (data.status === 'ready' && data.url) {
                        // Imagem pronta - exibir
                        displayImage(data.url);
                    } else {
                        // Atualizar status
                        updateStatus(data.status);

                        // Retry
                        const delay = (data.retry_after || 2) * 1000;
                        scheduleRetry(delay);
                    }
                } catch (error) {
                    console.error('Erro:', error);
                }
            }

            // Exibir imagem quando carregada
            function displayImage(url) {
                $img.on('load', function () {
                    // Fade in da imagem
                    $img.addClass('show');

                    // Remover skeleton após transição
                    setTimeout(() => {
                        $imageWrapper.removeClass('skeleton');
                        $statusBadge.remove();
                        $skeletonBody.addClass('d-none');
                        $realBody.removeClass('d-none');
                    }, 300);
                });

                $img.on('error', function () {
                    showError();
                });

                $img.attr('src', url);
            }

            // Atualizar badge de status
            function updateStatus(status) {
                const statusConfig = {
                    'queued': {text: 'Na fila', class: 'bg-secondary'},
                    'processing': {text: 'Processando', class: 'bg-primary'},
                    'completed': {text: 'Finalizando', class: 'bg-success'},
                    'failed': {text: 'Falhou', class: 'bg-danger'}
                };

                const config = statusConfig[status] || statusConfig['processing'];

                $statusBadge
                    .removeClass('d-none bg-secondary bg-primary bg-success bg-danger')
                    .addClass(config.class)
                    .html(`
                            ${status !== 'failed' ? '<span class="spinner-border spinner-border-sm me-1"></span>' : '<i class="bi bi-x-circle me-1"></i>'}
                            ${config.text}
                        `);
            }

            // Agendar retry
            function scheduleRetry(delay) {
                if (retryCount >= maxRetries) {
                    showError();
                    return;
                }

                retryCount++;
                const adjustedDelay = Math.min(delay * (1 + retryCount * 0.1), 5000);
                setTimeout(loadImage, adjustedDelay);
            }

            // Mostrar erro
            function showError() {
                $imageWrapper.html(`
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                            <div class="text-center">
                                <i class="bi bi-exclamation-triangle fs-1"></i>
                                <p class="mt-2 mb-0">Erro ao carregar</p>
                            </div>
                        </div>
                    `).removeClass('skeleton');
                $statusBadge.remove();
                $skeletonBody.remove();
            }

            // Iniciar carregamento
            loadImage();
        });
    };

    // Inicializar quando DOM estiver pronto
    $(document).ready(function () {
        const $grid = $('#imageGrid');

        // Criar cards para cada imagem
        config.images.forEach((imageData, index) => {
            const $col = $('<div>').addClass('col-12 col-md-6 col-lg-4');
            $grid.append($col);
            $col.asyncImage(imageData, config.params);
        });
    });
</script>
</body>
</html>
