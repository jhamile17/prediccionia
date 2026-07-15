const producto = document.getElementById("producto");
const cantidad = document.getElementById("cantidad");

const precio = document.getElementById("precio");
const stock = document.getElementById("stock");
const stockMinimo = document.getElementById("stock_minimo");

const mensaje = document.getElementById("mensaje-stock");
const btnRegistrar = document.getElementById("btnRegistrar");

let stockDisponible = 0;

// Mostrar la fecha actual automáticamente
window.addEventListener("load", () => {

    const fecha = document.querySelector("input[type='date']");

    if (fecha && !fecha.value) {

        const hoy = new Date();

        const yyyy = hoy.getFullYear();
        const mm = String(hoy.getMonth() + 1).padStart(2, "0");
        const dd = String(hoy.getDate()).padStart(2, "0");

        fecha.value = `${yyyy}-${mm}-${dd}`;

    }

});

// Cuando cambia el producto
producto.addEventListener("change", () => {

    const opcion = producto.options[producto.selectedIndex];

    if (producto.value === "") {

        precio.textContent = "S/. --";
        stock.textContent = "--";
        stockMinimo.textContent = "--";

        mensaje.textContent = "";

        btnRegistrar.disabled = false;

        return;

    }

    precio.textContent = "S/. " + opcion.dataset.precio;

    stock.textContent = opcion.dataset.stock + " unidades";

    stockMinimo.textContent = opcion.dataset.minimo + " unidades";

    stockDisponible = parseInt(opcion.dataset.stock);

    validarStock();

});

// Validar cantidad
cantidad.addEventListener("input", validarStock);

function validarStock() {

    const cantidadIngresada = parseInt(cantidad.value) || 0;

    if (cantidadIngresada > stockDisponible) {

        mensaje.style.color = "red";

        mensaje.textContent = "❌ Stock insuficiente.";

        btnRegistrar.disabled = true;

    }

    else {

        mensaje.style.color = "green";

        mensaje.textContent = "✔ Stock disponible.";

        btnRegistrar.disabled = false;

    }

}