import './bootstrap.js';
import './styles/css/app.min.css';

// Flash dismiss
window.flashDismiss = function (id){
    document.getElementById(`flash-${id}`).remove();
};