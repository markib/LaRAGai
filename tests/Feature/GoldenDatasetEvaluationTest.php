<?php

it('validates rag output against a golden answer using simple string similarity', function () {
    $goldenAnswer = 'The local RAG assistant should answer from the retrieved documents and mention Laravel plus React.';
    $candidateAnswer = 'The local RAG assistant responds using the retrieved document content and references Laravel and React.';

    expect($candidateAnswer)->toBeGoldenSimilarTo($goldenAnswer, 0.72);
});
