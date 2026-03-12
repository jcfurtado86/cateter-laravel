import './bootstrap';
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Mask from '@alpinejs/mask';
Alpine.plugin(Mask);
Livewire.start();
