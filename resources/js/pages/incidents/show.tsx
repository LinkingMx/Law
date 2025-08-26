import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Avatar, AvatarFallback, AvatarInitials } from '@/components/ui/avatar';
import { 
    AlertTriangle, 
    ArrowLeft, 
    Building2, 
    Calendar, 
    Clock, 
    Download, 
    MessageSquare, 
    Paperclip, 
    Send,
    User 
} from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Branch {
    id: number;
    name: string;
    address: string;
}

interface Comment {
    id: number;
    comment: string;
    created_at: string;
    is_internal: boolean;
    user: User;
}

interface Incident {
    id: number;
    title: string;
    description: string;
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    created_at: string;
    updated_at: string;
    resolved_at?: string;
    file_path?: string;
    file_name?: string;
    file_extension?: string;
    file_size?: number;
    mime_type?: string;
    user: User;
    branch: Branch;
    assigned_to?: User;
    comments: Comment[];
}

interface ShowIncidentPageProps {
    incident: Incident;
}

export default function ShowIncident({ incident }: ShowIncidentPageProps) {
    const { t } = useTranslations();
    
    const { data, setData, post, processing, reset } = useForm({
        comment: '',
    });

    const getStatusBadgeColor = (status: string) => {
        switch (status) {
            case 'open': return 'bg-warning/10 text-warning border-warning/30';
            case 'in_progress': return 'bg-info/10 text-info border-info/30';
            case 'resolved': return 'bg-success/10 text-success border-success/30';
            case 'closed': return 'bg-muted/10 text-muted-foreground border-border';
            default: return 'bg-muted/10 text-muted-foreground border-border';
        }
    };

    const getPriorityBadgeColor = (priority: string) => {
        switch (priority) {
            case 'low': return 'bg-success/10 text-success border-success/30';
            case 'medium': return 'bg-warning/10 text-warning border-warning/30';
            case 'high': return 'bg-destructive/10 text-destructive border-destructive/30';
            case 'urgent': return 'bg-destructive/10 text-destructive border-destructive/30';
            default: return 'bg-muted/10 text-muted-foreground border-border';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'open': return 'Abierta';
            case 'in_progress': return 'En Progreso';
            case 'resolved': return 'Resuelta';
            case 'closed': return 'Cerrada';
            default: return status;
        }
    };

    const getPriorityLabel = (priority: string) => {
        switch (priority) {
            case 'low': return 'Baja';
            case 'medium': return 'Media';
            case 'high': return 'Alta';
            case 'urgent': return 'Urgente';
            default: return priority;
        }
    };

    const formatFileSize = (bytes?: number) => {
        if (!bytes) return null;
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    const handleCommentSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!data.comment.trim()) return;
        
        post(`/incidents/${incident.id}/comments`, {
            onSuccess: () => {
                reset();
            }
        });
    };

    const getInitials = (name: string) => {
        return name.split(' ')
            .map(word => word[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const formatDateTime = (dateString: string) => {
        const date = new Date(dateString);
        return {
            date: date.toLocaleDateString('es-MX', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            }),
            time: date.toLocaleTimeString('es-MX', { 
                hour: '2-digit', 
                minute: '2-digit' 
            })
        };
    };

    return (
        <AppLayout>
            <Head title={`Incidencia: ${incident.title}`} />

            <div className="max-w-4xl mx-auto p-4 space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href="/incidents">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Volver
                        </Link>
                    </Button>
                    <div className="flex-1">
                        <h1 className="text-2xl font-semibold text-foreground line-clamp-2">
                            {incident.title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Incidencia #{incident.id}
                        </p>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Incident Details */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <CardTitle className="text-lg">Detalles de la Incidencia</CardTitle>
                                        <CardDescription>
                                            Información completa del problema reportado
                                        </CardDescription>
                                    </div>
                                    <div className="flex flex-col gap-2 flex-shrink-0">
                                        <Badge className={`text-sm px-3 py-1 ${getStatusBadgeColor(incident.status)}`}>
                                            {getStatusLabel(incident.status)}
                                        </Badge>
                                        <Badge className={`text-sm px-3 py-1 ${getPriorityBadgeColor(incident.priority)}`}>
                                            {getPriorityLabel(incident.priority)}
                                        </Badge>
                                    </div>
                                </div>
                            </CardHeader>
                            
                            <CardContent className="space-y-4">
                                <div>
                                    <h4 className="font-medium text-sm text-muted-foreground mb-2">Descripción</h4>
                                    <div className="prose prose-sm max-w-none">
                                        <p className="text-foreground whitespace-pre-wrap">
                                            {incident.description}
                                        </p>
                                    </div>
                                </div>

                                {/* File attachment */}
                                {incident.file_path && (
                                    <div>
                                        <h4 className="font-medium text-sm text-muted-foreground mb-2">Archivo adjunto</h4>
                                        <div className="flex items-center gap-3 p-3 bg-card border border-border rounded-lg">
                                            <div className="p-2 bg-primary/10 text-primary rounded">
                                                <Paperclip className="h-4 w-4" />
                                            </div>
                                            <div className="flex-1">
                                                <p className="text-sm font-medium">
                                                    {incident.file_name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {formatFileSize(incident.file_size)}
                                                </p>
                                            </div>
                                            <Button 
                                                size="sm" 
                                                variant="outline"
                                                asChild
                                            >
                                                <Link href={`/incidents/${incident.id}/download`}>
                                                    <Download className="h-4 w-4 mr-2" />
                                                    Descargar
                                                </Link>
                                            </Button>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Comments Section */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="text-lg flex items-center gap-2">
                                            <MessageSquare className="h-5 w-5" />
                                            Comentarios ({incident.comments.length})
                                        </CardTitle>
                                        <CardDescription>
                                            Conversación sobre esta incidencia
                                        </CardDescription>
                                    </div>
                                </div>
                            </CardHeader>
                            
                            <CardContent className="space-y-4">
                                {/* Comments List */}
                                <div className="space-y-4">
                                    {incident.comments.map((comment) => {
                                        const dateTime = formatDateTime(comment.created_at);
                                        return (
                                            <div key={comment.id} className="flex gap-3">
                                                <Avatar className="h-8 w-8 flex-shrink-0">
                                                    <AvatarFallback className="text-xs">
                                                        {getInitials(comment.user.name)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div className="flex-1 space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-medium text-sm">
                                                            {comment.user.name}
                                                        </span>
                                                        <span className="text-xs text-muted-foreground">
                                                            {dateTime.date} a las {dateTime.time}
                                                        </span>
                                                    </div>
                                                    <div className="bg-card border border-border rounded-lg p-3">
                                                        <p className="text-sm whitespace-pre-wrap">
                                                            {comment.comment}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>

                                {incident.comments.length === 0 && (
                                    <div className="text-center py-8">
                                        <MessageSquare className="mx-auto h-8 w-8 text-muted-foreground mb-2" />
                                        <p className="text-sm text-muted-foreground">
                                            Aún no hay comentarios en esta incidencia
                                        </p>
                                    </div>
                                )}

                                {/* Add Comment Form */}
                                <Separator />
                                <form onSubmit={handleCommentSubmit} className="space-y-3">
                                    <div>
                                        <Label htmlFor="comment" className="text-sm font-medium">
                                            Agregar comentario
                                        </Label>
                                        <Textarea
                                            id="comment"
                                            placeholder="Escribe tu comentario..."
                                            value={data.comment}
                                            onChange={(e) => setData('comment', e.target.value)}
                                            rows={3}
                                            className="mt-1"
                                        />
                                    </div>
                                    <div className="flex justify-end">
                                        <Button 
                                            type="submit" 
                                            disabled={processing || !data.comment.trim()}
                                            size="sm"
                                        >
                                            <Send className="h-4 w-4 mr-2" />
                                            {processing ? 'Enviando...' : 'Comentar'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-4">
                        {/* Info Card */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Información</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-3">
                                    <div>
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                                            <User className="h-3 w-3" />
                                            Reportado por
                                        </div>
                                        <p className="text-sm font-medium">{incident.user.name}</p>
                                    </div>

                                    <div>
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                                            <Building2 className="h-3 w-3" />
                                            Sucursal
                                        </div>
                                        <p className="text-sm font-medium">{incident.branch.name}</p>
                                        <p className="text-xs text-muted-foreground">{incident.branch.address}</p>
                                    </div>

                                    {incident.assigned_to && (
                                        <div>
                                            <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                                                <User className="h-3 w-3" />
                                                Asignado a
                                            </div>
                                            <p className="text-sm font-medium">{incident.assigned_to.name}</p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Dates Card */}
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base">Fechas</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                                        <Calendar className="h-3 w-3" />
                                        Creado
                                    </div>
                                    <p className="text-sm">{formatDateTime(incident.created_at).date}</p>
                                    <p className="text-xs text-muted-foreground">{formatDateTime(incident.created_at).time}</p>
                                </div>

                                <div>
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                                        <Clock className="h-3 w-3" />
                                        Última actualización
                                    </div>
                                    <p className="text-sm">{formatDateTime(incident.updated_at).date}</p>
                                    <p className="text-xs text-muted-foreground">{formatDateTime(incident.updated_at).time}</p>
                                </div>

                                {incident.resolved_at && (
                                    <div>
                                        <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                                            <Calendar className="h-3 w-3" />
                                            Resuelto
                                        </div>
                                        <p className="text-sm">{formatDateTime(incident.resolved_at).date}</p>
                                        <p className="text-xs text-muted-foreground">{formatDateTime(incident.resolved_at).time}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}