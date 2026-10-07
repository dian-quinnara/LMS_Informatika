@extends('layouts.app')

@section('title', $material->title)

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">MATERI SEARCHING</span><h1>{{ strtoupper($material->title) }}</h1><p>{{ $material->summary }}</p></div>
        <a class="button button-quiet" href="{{ route('materi.searching') }}"><span aria-hidden="true">&#8592;</span> Kembali ke Searching</a>
    </section>
    <article class="reading-panel">
        <span class="section-kicker">MATERI PEMBELAJARAN</span>
        @foreach ($sections as $section)
            <section>
                <div class="section-heading"><div><span class="section-kicker">SEARCHING</span><h2>{{ $section['title'] }}</h2></div></div>
                @if (isset($section['intro']))
                    <p>{{ $section['intro'] }}</p>
                @endif
                @switch($section['type'])
                    @case('paragraphs')
                        @foreach ($section['paragraphs'] as $paragraph)
                            <p>{{ $paragraph }}</p>
                        @endforeach
                        @break
                    @case('list')
                        @if (isset($section['items']))
                            <ul>
                                @foreach ($section['items'] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        @endif
                        @break
                    @case('steps')
                        @foreach ($section['items'] as $step)
                            <article class="list-row">
                                <div><strong>{{ $step['title'] }}</strong><small>{{ $step['detail'] }}</small></div>
                                <span class="row-mark">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            </article>
                        @endforeach
                        @break
                    @case('array')
                        <p>Target: <strong>{{ $section['target'] }}</strong></p>
                        <ol>
                            @foreach ($section['values'] as $value)
                                <li>
                                    <code>{{ $value }}</code>
                                    @if ($loop->index === $section['match_index'])
                                        <strong> ← target ditemukan</strong>
                                    @else
                                        <span> ← dibandingkan, belum cocok</span>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                        @break
                    @case('binary_illustration')
                        <p>Target: <strong>25</strong></p>
                        @foreach ($section['states'] as $state)
                            <article class="list-row">
                                <div>
                                    <strong>low={{ $state['low'] }}, high={{ $state['high'] }}, mid={{ $state['mid'] }} (nilai {{ $section['values'][$state['mid']] }})</strong>
                                    <small>{{ $state['description'] }}</small>
                                    <div>
                                        @foreach ($section['values'] as $index => $value)
                                            <span class="{{ in_array($index, $state['remaining'], true) ? 'active-badge' : 'draft-badge' }}">{{ $value }}@if ($index === $state['mid']) · mid @endif</span>
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @endforeach
                        @break
                    @case('code')
                        <p><strong>{{ $section['language'] }}</strong></p>
                        <pre><code>{{ $section['code'] }}</code></pre>
                        @break
                    @case('table')
                        <div class="reading-content">
                            <table>
                                <thead><tr>@foreach ($section['headers'] as $header)<th scope="col">{{ $header }}</th>@endforeach</tr></thead>
                                <tbody>
                                    @foreach ($section['rows'] as $row)
                                        <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @break
                @endswitch
                @if (isset($section['outro']))
                    <p>{{ $section['outro'] }}</p>
                @endif
            </section>
        @endforeach
    </article>
    <p><a class="button button-quiet" href="{{ route('materi.searching') }}"><span aria-hidden="true">&#8592;</span> Kembali ke Searching</a></p>
@endsection