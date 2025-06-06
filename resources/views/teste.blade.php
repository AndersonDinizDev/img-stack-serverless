<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="
        crossorigin="anonymous"></script>
    <title>Teste</title>

    <style>
    </style>
</head>

<body>
    <main class="container">
        <div class="row min-vh-100 align-items-center">
            <div id="card-container" class="bg-light shadow-sm col d-flex gap-3 flex-wrap">
                <!-- Cards serão inseridos aqui via JavaScript -->
            </div>
        </div>
    </main>

    <script>
        const state = {
            cardContainer: $("#card-container"),
            api: "https://dzy9qlgb7cwht.cloudfront.net/api/v1/image",
            images: [
                'https://images.pexels.com/photos/2662116/pexels-photo-2662116.jpeg',
                'https://images.pexels.com/photos/1438761/pexels-photo-1438761.jpeg',
                'https://images.pexels.com/photos/3225531/pexels-photo-3225531.jpeg',
                'https://images.pexels.com/photos/1680140/pexels-photo-1680140.jpeg',
                'https://images.pexels.com/photos/2253275/pexels-photo-2253275.jpeg'
            ],
            config: {
                skeleton: 'auto',
                width: '300',
                height: '200',
                quality: '100',
                transform: 'resize',
                format: 'jpeg'
            }
        }

        function createCard(item, index) {
            const imageUrl =
                `${state.api}?image=${item}&width=${state.config.width}&height=${state.config.height}&quality=${state.config.quality}&skeleton=${state.config.skeleton}&transform=${state.config.transform}&format=${state.config.format}`;

            return `
            <div>
                        <object data="${imageUrl}"
                               class="card-img-top" width="300px" height="200px">
                        </object>
            </div>
            `;
        }

        function renderCards() {
            state.images.forEach(function(item, index) {
                const cardHtml = createCard(item, index);
                state.cardContainer.append(cardHtml);
            });
        }

        $(document).ready(() => {
            renderCards();
        });
    </script>
</body>

</html>
