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
    fecha_movimiento: new Date().toISOString().slice(0, 10),
    hora_movimiento: new Date().toTimeString().slice(0, 5),
  });
  const [file, setFile] = useState(null);
  const [currentAttachment, setCurrentAttachment] = useState(null);
  const BACKEND_URL = import.meta.env.VITE_API_URL?.replace('/api', '') || 'http://localhost:8080';

  useEffect(() => {
    if (movement) {
      setFormData({
        tipo: movement.tipo,
        id_cuenta: movement.id_cuenta,
        cantidad: movement.cantidad,
        notas: movement.notas || '',
        fecha_movimiento: movement.fecha_movimiento.slice(0, 10),
        hora_movimiento: movement.fecha_movimiento.slice(11, 16), // Extraer HH:MM
      });
      setCurrentAttachment(movement.adjunto || null);
    } else {
      setFormData({
        tipo: 'ingreso',
        id_cuenta: accounts[0]?.id || '',
        cantidad: '',
        notas: '',
        fecha_movimiento: new Date().toISOString().slice(0, 10),
        hora_movimiento: new Date().toTimeString().slice(0, 5),
      });
      setCurrentAttachment(null);
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
      formDataToSend.append('fecha_movimiento', `${formData.fecha_movimiento} ${formData.hora_movimiento}:00`);

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
          label="Fecha"
          type="date"
          value={formData.fecha_movimiento}
          onChange={(e) => setFormData({ ...formData, fecha_movimiento: e.target.value })}
          required
        />

        <Input
          label="Hora"
          type="time"
          value={formData.hora_movimiento}
          onChange={(e) => setFormData({ ...formData, hora_movimiento: e.target.value })}
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

          {/* Mostrar adjunto actual si existe */}
          {isEdit && currentAttachment && !file && (
            <div className="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <Paperclip size={18} className="text-gray-500" />
                  <a
                    href={`${BACKEND_URL}/uploads/movements/${currentAttachment}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-sm text-primary-600 hover:text-primary-800 underline"
                  >
                    Ver archivo actual
                  </a>
                </div>
                <button
                  type="button"
                  onClick={async () => {
                    if (window.confirm('¿Eliminar el archivo adjunto? Esta acción no se puede deshacer.')) {
                      try {
                        await movementsAPI.deleteAttachment(movement.id);
                        setCurrentAttachment(null);
                        toast.success('Archivo eliminado');
                      } catch (error) {
                        toast.error('Error al eliminar archivo');
                      }
                    }
                  }}
                  className="px-3 py-1 text-sm text-red-600 hover:text-red-800 hover:bg-red-50 rounded"
                >
                  Eliminar
                </button>
              </div>
            </div>
          )}

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
              {isEdit && currentAttachment ? 'Reemplazar archivo' : 'Seleccionar archivo'}
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
  const [fileType, setFileType] = useState('json');
  const [result, setResult] = useState(null);

  const handleFileChange = (e) => {
    const selectedFile = e.target.files[0];
    if (selectedFile) {
      setFile(selectedFile);
      setResult(null); // Limpiar resultados previos
      const ext = selectedFile.name.split('.').pop().toLowerCase();
      setFileType(ext === 'csv' ? 'csv' : 'json');
    }
  };

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

      let response;
      if (fileType === 'csv') {
        response = await movementsAPI.importCSV(formData);
      } else {
        response = await movementsAPI.import(formData);
      }

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
      title="Importar Backup"
      size="lg"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        {/* Advertencia de seguridad */}
        <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4">
          <div className="flex">
            <div className="flex-shrink-0">
              <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
              </svg>
            </div>
            <div className="ml-3">
              <h3 className="text-sm font-medium text-yellow-800">
                ⚠️ Importante: Prevención de duplicados
              </h3>
              <div className="mt-2 text-sm text-yellow-700">
                <ul className="list-disc list-inside space-y-1">
                  <li><strong>No puedes importar un backup en la misma cuenta</strong> donde fue creado</li>
                  <li>El backup está diseñado para <strong>restaurar en un usuario diferente</strong></li>
                  <li>Si las cuentas no existen, se crearán automáticamente</li>
                  <li>Si ya existen cuentas con el mismo nombre, se usarán las existentes</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        {/* Información del formato */}
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 className="font-medium text-blue-900 mb-2 flex items-center gap-2">
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Información del Backup
          </h4>
          <ul className="text-sm text-blue-800 space-y-1 list-disc list-inside">
            <li>Formato: JSON v2.0 con estructura completa</li>
            <li>Incluye: Todas las cuentas y movimientos</li>
            <li>Adjuntos: Se restauran automáticamente</li>
            <li>Balance: Se recalcula automáticamente</li>
          </ul>
        </div>

        {/* Selector de archivo */}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Archivo de Backup (JSON)
          </label>
          <div className="mt-1">
            <input
              type="file"
              onChange={handleFileChange}
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

        {/* Resultados de la importación */}
        {result && (
          <div className="space-y-3">
            {/* Éxito */}
            <div className="bg-green-50 border border-green-200 rounded-lg p-4">
              <p className="text-sm font-medium text-green-900 mb-2">
                ✅ Importación completada
              </p>
              <ul className="text-sm text-green-800 space-y-1">
                <li>• <strong>{result.imported}</strong> movimientos importados</li>
                {result.cuentas_creadas && result.cuentas_creadas.length > 0 && (
                  <li>• <strong>{result.cuentas_creadas.length}</strong> cuentas creadas: {result.cuentas_creadas.join(', ')}</li>
                )}
                {result.cuentas_existentes && result.cuentas_existentes.length > 0 && (
                  <li>• <strong>{result.cuentas_existentes.length}</strong> cuentas existentes usadas: {result.cuentas_existentes.join(', ')}</li>
                )}
              </ul>
            </div>

            {/* Errores */}
            {result.errors && result.errors.length > 0 && (
              <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                <p className="text-sm font-medium text-red-900 mb-2">
                  ⚠️ {result.errors.length} advertencias/errores:
                </p>
                <ul className="text-xs text-red-800 space-y-1 max-h-32 overflow-y-auto">
                  {result.errors.map((error, index) => (
                    <li key={index} className="break-words">{error}</li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        )}

        {/* Botones de acción */}
        <div className="flex gap-3 pt-4 border-t border-gray-200">
          <Button
            type="button"
            variant="secondary"
            onClick={() => {
              setFile(null);
              setResult(null);
              onClose();
            }}
            className="flex-1"
          >
            {result ? 'Cerrar' : 'Cancelar'}
          </Button>
          {!result && (
            <Button type="submit" loading={loading} disabled={!file} className="flex-1">
              <Upload size={20} className="mr-2" />
              Importar Backup
            </Button>
          )}
        </div>
      </form>
    </Modal>
  );
};