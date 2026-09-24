<?php

test('guests are redirected to login from the root page', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});
