import { Head, Link, useForm, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { AlertTriangle, ArrowLeft, Upload, X } from 'lucide-react';
import { useState } from 'react';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';

interface Branch {
    id: number;
    name: string;
    address: string;
}

interface CreateIncidentPageProps {
    branches: Branch[];
}

export default function CreateIncident({ branches }: CreateIncidentPageProps) {
    const { t } = useTranslations();
    const [selectedFile, setSelectedFile] = useState<File | null>(null);
    
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        priority: 'medium' as 'low' | 'medium' | 'high' | 'urgent',
        branch_id: '',
        file: null as File | null,
    });

    const handleFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] || null;
        setSelectedFile(file);
        setData('file', file);
    };

    const removeFile = () => {
        setSelectedFile(null);
        setData('file', null);
        // Reset the file input
        const fileInput = document.getElementById('file') as HTMLInputElement;
        if (fileInput) {
            fileInput.value = '';
        }
    };

    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        const formData = new FormData();
        formData.append('title', data.title);
        formData.append('description', data.description);
        formData.append('priority', data.priority);
        formData.append('branch_id', data.branch_id);
        
        if (data.file) {
            formData.append('file', data.file);
        }
        
        router.post('/incidents', formData, {
            forceFormData: true,
            onSuccess: () => reset(),
            onError: (errors) => {
                console.error('Error creating incident:', errors);
            }
        });
    };

    return (
        <AppLayout>
            <Head title="Nueva Incidencia" />

            <div className="max-w-2xl mx-auto p-4 space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/incidents">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Link>
                    </Button>
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">Nueva Incidencia</h1>
                        <p className="text-sm text-muted-foreground">
                            Reporta un problema o solicita asistencia del equipo legal
                        </p>
                    </div>
                </div>

                {/* Form */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <AlertTriangle className="h-5 w-5 text-primary" />
                            Información de la Incidencia
                        </CardTitle>
                        <CardDescription>
                            Proporciona todos los detalles necesarios para que podamos atender tu solicitud de manera efectiva.
                        </CardDescription>
                    </CardHeader>
                    
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Title */}
                            <div className="space-y-2">
                                <Label htmlFor="title" className="text-sm font-medium">
                                    Título de la Incidencia *
                                </Label>
                                <Input
                                    id="title"
                                    type="text"
                                    placeholder="Describe brevemente el problema"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className={errors.title ? 'border-destructive' : ''}
                                    required
                                />
                                {errors.title && (
                                    <p className="text-sm text-destructive">{errors.title}</p>
                                )}
                            </div>

                            {/* Branch and Priority */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="branch_id" className="text-sm font-medium">
                                        Sucursal *
                                    </Label>
                                    <Select 
                                        value={data.branch_id} 
                                        onValueChange={(value) => setData('branch_id', value)}
                                    >
                                        <SelectTrigger className={errors.branch_id ? 'border-destructive' : ''}>
                                            <SelectValue placeholder="Selecciona una sucursal" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {branches.map((branch) => (
                                                <SelectItem key={branch.id} value={branch.id.toString()}>
                                                    {branch.name} - {branch.address}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.branch_id && (
                                        <p className="text-sm text-destructive">{errors.branch_id}</p>
                                    )}
                                </div>
                                
                                <div className="space-y-2">
                                    <Label htmlFor="priority" className="text-sm font-medium">
                                        Prioridad *
                                    </Label>
                                    <Select 
                                        value={data.priority} 
                                        onValueChange={(value) => setData('priority', value as typeof data.priority)}
                                    >
                                        <SelectTrigger className={errors.priority ? 'border-destructive' : ''}>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="low">Baja</SelectItem>
                                            <SelectItem value="medium">Media</SelectItem>
                                            <SelectItem value="high">Alta</SelectItem>
                                            <SelectItem value="urgent">Urgente</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.priority && (
                                        <p className="text-sm text-destructive">{errors.priority}</p>
                                    )}
                                </div>
                            </div>

                            {/* Description */}
                            <div className="space-y-2">
                                <Label htmlFor="description" className="text-sm font-medium">
                                    Descripción Detallada *
                                </Label>
                                <Textarea
                                    id="description"
                                    placeholder="Proporciona una descripción detallada del problema, incluyendo pasos para reproducirlo si aplica..."
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    className={errors.description ? 'border-destructive' : ''}
                                    rows={6}
                                    required
                                />
                                {errors.description && (
                                    <p className="text-sm text-destructive">{errors.description}</p>
                                )}
                            </div>

                            {/* File Upload */}
                            <div className="space-y-2">
                                <Label htmlFor="file" className="text-sm font-medium">
                                    Archivo Relacionado (Opcional)
                                </Label>
                                <div className="space-y-3">
                                    {!selectedFile ? (
                                        <div className="border-2 border-dashed border-border rounded-lg p-6 text-center hover:border-primary/50 transition-colors">
                                            <Upload className="mx-auto h-8 w-8 text-muted-foreground mb-2" />
                                            <div className="space-y-2">
                                                <p className="text-sm text-muted-foreground">
                                                    Arrastra un archivo aquí o haz clic para seleccionar
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF, TXT, ZIP (máx. 25MB)
                                                </p>
                                            </div>
                                            <input
                                                id="file"
                                                type="file"
                                                className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.zip"
                                                onChange={handleFileChange}
                                            />
                                        </div>
                                    ) : (
                                        <div className="flex items-center justify-between p-3 bg-card border border-border rounded-lg">
                                            <div className="flex items-center gap-3">
                                                <div className="p-2 bg-primary/10 text-primary rounded">
                                                    <Upload className="h-4 w-4" />
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium">{selectedFile.name}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {formatFileSize(selectedFile.size)}
                                                    </p>
                                                </div>
                                            </div>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={removeFile}
                                            >
                                                <X className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    )}
                                </div>
                                {errors.file && (
                                    <p className="text-sm text-destructive">{errors.file}</p>
                                )}
                            </div>

                            {/* Actions */}
                            <div className="flex items-center gap-3 pt-4">
                                <Button 
                                    type="submit" 
                                    disabled={processing}
                                    className="flex-1 md:flex-initial"
                                >
                                    {processing ? 'Creando...' : 'Crear Incidencia'}
                                </Button>
                                <Button 
                                    type="button" 
                                    variant="outline"
                                    asChild
                                >
                                    <Link href="/incidents">
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}