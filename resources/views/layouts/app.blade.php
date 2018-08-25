<!DOCTYPE html>
<html>
<head>
    <title>Awesome Application</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="//cdn.shopify.com/s/assets/external/app.js"></script>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/uptown.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/chosen.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/chosen.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('css/color-picker.min.css') }}">
    <link href='https://fonts.googleapis.com/css?family=Montez|Lobster|Josefin+Sans|Shadows+Into+Light|Pacifico|Amatic+SC:700|Orbitron:400,900|Rokkitt|Righteous|Dancing+Script:700|Bangers|Chewy|Sigmar+One|Architects+Daughter|Abril+Fatface|Covered+By+Your+Grace|Kaushan+Script|Gloria+Hallelujah|Satisfy|Lobster+Two:700|Comfortaa:700|Cinzel|Courgette' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/chosen.css') }}">
    @yield('styles')
        <script type="text/javascript">

            ShopifyApp.init({
                  apiKey: "{{ env('SHOPIFY_APIKEY') }}",
                  shopOrigin: '{{ "https://" . session("domain") }}'
            });

        </script>

        <script type="text/javascript">
            
                ShopifyApp.ready(function(){

                    ShopifyApp.Bar.initialize({

                        icon : '',
                        title  : 'Awesome Application',
                        buttons : {
                            primary : {
                                label : 'Help',
                                message : 'Help'
                            }
                        }

                    });

                });

        </script>
</head>
<body>
    
    <main>
        @yield('content')
    </main>
    
    <script src="http://code.jquery.com/jquery-3.2.1.min.js" 
    integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
    <script src="{{  asset('js/app.js' )}}"></script>
    <script src="{{  asset('js/chosen.jquery.js' )}}"></script>
    <script src="{{  asset('js/init.js' )}}"></script>
    <script src="{{  asset('js/prism.js' )}}"></script>
    <script src="{{  asset('js/color-picker.min.js' )}}"></script>
    @yield('scripts')
</body>
</html>