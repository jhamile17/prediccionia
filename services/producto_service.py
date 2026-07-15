from models.producto_model import ProductoModel


class ProductoService:

    @staticmethod
    def listar():

        return ProductoModel.obtener_todos()