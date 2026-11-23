{{-- Title, description and link preview of a page. $image: full address or null. $noindex: hide the page from search engines. --}}
@props(['title', 'description', 'canonical', 'siteName', 'image' => null, 'noindex' => false])
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
@if ($noindex)
<meta name="robots" content="noindex, follow">
@else
<link rel="canonical" href="{{ $canonical }}">
@endif
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
@if ($image)
<meta property="og:image" content="{{ $image }}">
@endif
<meta name="twitter:card" content="{{ $image ? 'summary_large_image' : 'summary' }}">
