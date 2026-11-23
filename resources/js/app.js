import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { postIndex } from './posts-index';

Alpine.plugin(collapse);

document.addEventListener('alpine:init', () => {
    Alpine.data('postIndex', postIndex);
});

window.Alpine = Alpine;
Alpine.start();
