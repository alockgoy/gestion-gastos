// Añadir estos componentes al final de MovementsPage.jsx
import { useState, useEffect } from 'react';
import { Modal } from '../common/Modal';
import { Button } from '../common/Button';
import { Input } from '../common/Input';
import { Select } from '../common/Select';
import { Paperclip, Upload, X } from 'lucide-react';
import { movementsAPI } from '../../services/api';
import toast from 'react-hot-toast';
// ============================================
// MovementModal Component
// ============================================
export const MovementModal = ({ isOpen, onClose, onSuccess, accounts, movement = null }) => {
  const isEdit = !!movement;
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    tipo: 'ingreso',
    id_cuenta: '',
    cantidad: '',
    notas: '',
    fecha_movimiento: new Date().toISOString().slice(0, 16),
  });
  const [file, setFile] = useState(null);

  useEffect(() => {
    if (movement) {
      setFormData({
        tipo: movement.tipo,
        id_cuenta: movement.id_cuenta,
        cantidad: movement.cantidad,
        notas: movement.notas || '',
        fecha_movimiento: movement.fecha_movimiento.slice(0, 16),
      });
    } else {
      setFormData({
        tipo: 'ingreso',
        id_cuenta: accounts[0]?.id || '',
        cantidad: '',
        notas: '',
        fecha_movimiento: new Date().toISOString().slice(0, 16),
      });
      setFile(null);
    }
  }, [movement, accounts, isOpen]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const formDataToSend = new FormData();
      formDataToSend.append('tipo', formData.tipo);
      formDataToSend.append('id_cuenta', formData.id_cuenta);
      formDataToSend.append('cantidad', formData.cantidad);
      formDataToSend.append('notas', formData.notas);
      formDataToSend.append('fecha_movimiento', formData.fecha_movimiento);
      
      if (file) {
        formDataToSend.append('adjunto', file);
      }

      if (isEdit) {
        await movementsAPI.update(movement.id, formDataToSend);
        toast.success('Movimiento actualizado');
      } else {
        await movementsAPI.create(formDataToSend);
        toast.success('Movimiento creado');
      }
      onSuccess();
    } catch (error) {
      const message = error.response?.data?.message || 'Error al guardar';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEdit ? 'Editar Movimiento' : 'Nuevo Movimiento'}
      size="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="grid grid-cols-2 gap-4">
          <Select
            label="Tipo"
            value={formData.tipo}
            onChange={(e) => setFormData({ ...formData, tipo: e.target.value })}
            options={[
              { value: 'ingreso', label: '💰 Ingreso' },
              { value: 'retirada', label: '💸 Gasto' },
            ]}
            required
          />

          <Select
            label="Cuenta"
            value={formData.id_cuenta}
            onChange={(e) => setFormData({ ...formData, id_cuenta: e.target.value })}
            options={accounts.map((acc) => ({
              value: acc.id,
              label: acc.nombre,
            }))}
            required
          />
        </div>

        <Input
          label="Cantidad"
          type="number"
          step="0.01"
          min="0.01"
          value={formData.cantidad}
          onChange={(e) => setFormData({ ...formData, cantidad: e.target.value })}
          placeholder="0.00"
          required
        />

        <Input
          label="Fecha y hora"
          type="datetime-local"
          value={formData.fecha_movimiento}
          onChange={(e) => setFormData({ ...formData, fecha_movimiento: e.target.value })}
          required
        />

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Notas (opcional)
          </label>
          <textarea
            className="input"
            value={formData.notas}
            onChange={(e) => setFormData({ ...formData, notas: e.target.value })}
            placeholder="Describe este movimiento..."
            rows="3"
            maxLength="1000"
          />
          <p className="mt-1 text-xs text-gray-500">
            {formData.notas.length}/1000 caracteres
          </p>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Archivo adjunto (opcional)
          </label>
          <div className="mt-1 flex items-center gap-3">
            <input
              type="file"
              onChange={(e) => setFile(e.target.files[0])}
              accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
              className="hidden"
              id="file-upload"
            />
            <label
              htmlFor="file-upload"
              className="cursor-pointer inline-flex items-center gap-2 px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
            >
              <Paperclip size={18} />
              Seleccionar archivo
            </label>
            {file && (
              <div className="flex items-center gap-2">
                <span className="text-sm text-gray-600">{file.name}</span>
                <button
                  type="button"
                  onClick={() => setFile(null)}
                  className="text-red-600 hover:text-red-800"
                >
                  <X size={18} />
                </button>
              </div>
            )}
          </div>
          <p className="mt-1 text-xs text-gray-500">
            PDF o imágenes (JPG, PNG, GIF, WEBP). Máximo 5 MB.
          </p>
        </div>

        <div className="flex gap-3 pt-4 border-t border-gray-200">
          <Button
            type="button"
            variant="secondary"
            onClick={onClose}
            className="flex-1"
          >
            Cancelar
          </Button>
          <Button type="submit" loading={loading} className="flex-1">
            {isEdit ? 'Actualizar' : 'Crear'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};

// ============================================
// ImportModal Component
// ============================================
export const ImportModal = ({ isOpen, onClose, onSuccess }) => {
  const [loading, setLoading] = useState(false);
  const [file, setFile] = useState(null);
  const [result, setResult] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!file) {
      toast.error('Selecciona un archivo');
      return;
    }

    setLoading(true);
    setResult(null);

    try {
      const formData = new FormData();
      formData.append('file', file);

      const response = await movementsAPI.import(formData);
      const data = response.data.data;
      
      setResult(data);
      
      if (data.imported > 0) {
        toast.success(`${data.imported} movimientos importados`);
        if (data.errors.length === 0) {
          setTimeout(() => {
            onSuccess();
          }, 2000);
        }
      } else {
        toast.error('No se pudo importar ningún movimiento');
      }
    } catch (error) {
      const message = error.response?.data?.message || 'Error al importar';
      toast.error(message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Importar Movimientos"
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 className="font-medium text-blue-900 mb-2">ℹ️ Información</h4>
          <ul className="text-sm text-blue-800 space-y-1 list-disc list-inside">
            <li>Solo se aceptan archivos JSON</li>
            <li>El formato debe ser el mismo que al exportar</li>
            <li>Las cuentas deben existir previamente</li>
            <li>Los movimientos duplicados serán omitidos</li>
          </ul>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Archivo JSON
          </label>
          <div className="mt-1">
            <input
              type="file"
              onChange={(e) => setFile(e.target.files[0])}
              accept=".json"
              className="hidden"
              id="import-file"
            />
            <label
              htmlFor="import-file"
              className="cursor-pointer flex items-center justify-center gap-2 w-full px-4 py-8 border-2 border-dashed border-gray-300 rounded-lg hover:border-primary-500 hover:bg-primary-50 transition-all"
            >
              <Upload size={24} className="text-gray-400" />
              <div className="text-center">
                <p className="text-sm font-medium text-gray-700">
                  {file ? file.name : 'Selecciona un archivo JSON'}
                </p>
                <p className="text-xs text-gray-500 mt-1">
                  O arrastra y suelta aquí
                </p>
              </div>
            </label>
          </div>
        </div>

        {result && (
          <div className="space-y-3">
            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
              <p className="text-sm font-medium text-green-900">
                ✅ {result.imported} movimientos importados correctamente
              </p>
            </div>

            {result.errors.length > 0 && (
              <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <p className="text-sm font-medium text-red-900 mb-2">
                  ⚠️ {result.errors.length} errores encontrados:
                </p>
                <ul className="text-xs text-red-800 space-y-1 max-h-32 overflow-y-auto">
                  {result.errors.map((error, index) => (
                    <li key={index}>{error}</li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        )}

        <div className="flex gap-3 pt-4 border-t border-gray-200">
          <Button
            type="button"
            variant="secondary"
            onClick={onClose}
            className="flex-1"
          >
            {result ? 'Cerrar' : 'Cancelar'}
          </Button>
          {!result && (
            <Button type="submit" loading={loading} className="flex-1">
              Importar
            </Button>
          )}
        </div>
      </form>
    </Modal>
  );
};