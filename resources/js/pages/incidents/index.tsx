import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { AlertTriangle, Plus, Search, User, Building2, MessageSquare, Paperclip, Filter } from 'lucide-react';
import { useState } from 'react';
import { useTranslations } from '@/hooks/use-translations';
import AppLayout from '@/layouts/app-layout';

interface Incident {
    id: number;
    title: string;
    description: string;
    status: 'open' | 'in_progress' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    created_at: string;
    updated_at: string;
    user: {
        id: number;
        name: string;
    };
    branch: {
        id: number;
        name: string;
    };
    assigned_to?: {
        id: number;
        name: string;
    };
    comments_count: number;
    has_file: boolean;
}

interface Stats {
    total: number;
    open: number;
    in_progress: number;
    resolved: number;
}

interface Filters {
    status?: string;
    priority?: string;
    search?: string;
}

interface PaginatedIncidents {
    data: Incident[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface IncidentsPageProps {
    incidents: PaginatedIncidents;
    stats: Stats;
    filters: Filters;
}

export default function IncidentsIndex({ incidents, stats, filters }: IncidentsPageProps) {
    const { t } = useTranslations();
    const [searchTerm, setSearchTerm] = useState(filters.search || '');

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

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/incidents', { 
            ...filters, 
            search: searchTerm || undefined,
        }, { preserveState: true });
    };

    const handleFilter = (key: string, value: string | undefined) => {
        router.get('/incidents', { 
            ...filters, 
            [key]: (value === 'all' || !value) ? undefined : value,
        }, { preserveState: true });
    };

    const clearFilters = () => {
        router.get('/incidents', {}, { preserveState: true });
        setSearchTerm('');
    };

    return (
        <AppLayout>
            <Head title="Incidencias" />

            <div className="p-4 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold text-foreground">Incidencias</h1>
                        <p className="text-sm text-muted-foreground">
                            Gestiona y da seguimiento a las incidencias reportadas
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/incidents/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Nueva Incidencia
                        </Link>
                    </Button>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="stat-card">
                        <CardContent className="p-3">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-muted-foreground">Total</p>
                                    <p className="text-xl font-semibold">{stats.total}</p>
                                </div>
                                <div className="icon-bg-primary">
                                    <AlertTriangle className="h-4 w-4" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card className="stat-card-warning">
                        <CardContent className="p-3">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-muted-foreground">Abiertas</p>
                                    <p className="text-xl font-semibold text-warning">{stats.open}</p>
                                </div>
                                <div className="icon-bg-warning">
                                    <AlertTriangle className="h-4 w-4" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card className="stat-card-info">
                        <CardContent className="p-3">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-muted-foreground">En Progreso</p>
                                    <p className="text-xl font-semibold text-info">{stats.in_progress}</p>
                                </div>
                                <div className="icon-bg-info">
                                    <AlertTriangle className="h-4 w-4" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                    
                    <Card className="stat-card-success">
                        <CardContent className="p-3">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs text-muted-foreground">Resueltas</p>
                                    <p className="text-xl font-semibold text-success">{stats.resolved}</p>
                                </div>
                                <div className="icon-bg-success">
                                    <AlertTriangle className="h-4 w-4" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader className="pb-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Filter className="h-4 w-4 text-muted-foreground" />
                                <CardTitle className="text-lg">Filtros</CardTitle>
                            </div>
                            {(filters.status || filters.priority || filters.search) && (
                                <Button 
                                    variant="ghost" 
                                    size="sm"
                                    onClick={clearFilters}
                                >
                                    Limpiar filtros
                                </Button>
                            )}
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="flex-1">
                                <Input
                                    type="text"
                                    placeholder="Buscar por título o descripción..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                    className="pr-10"
                                />
                            </div>
                            <Button type="submit" size="sm">
                                <Search className="h-4 w-4" />
                            </Button>
                        </form>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="text-sm font-medium mb-2 block">Estado</label>
                                <Select 
                                    value={filters.status || "all"} 
                                    onValueChange={(value) => handleFilter('status', value || undefined)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todos los estados" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todos los estados</SelectItem>
                                        <SelectItem value="open">Abierta</SelectItem>
                                        <SelectItem value="in_progress">En Progreso</SelectItem>
                                        <SelectItem value="resolved">Resuelta</SelectItem>
                                        <SelectItem value="closed">Cerrada</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            
                            <div>
                                <label className="text-sm font-medium mb-2 block">Prioridad</label>
                                <Select 
                                    value={filters.priority || "all"} 
                                    onValueChange={(value) => handleFilter('priority', value || undefined)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Todas las prioridades" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas las prioridades</SelectItem>
                                        <SelectItem value="low">Baja</SelectItem>
                                        <SelectItem value="medium">Media</SelectItem>
                                        <SelectItem value="high">Alta</SelectItem>
                                        <SelectItem value="urgent">Urgente</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Incidents Grid */}
                <div className="grid grid-cols-1 gap-4">
                    {incidents.data.map((incident) => (
                        <Link 
                            key={incident.id} 
                            href={`/incidents/${incident.id}`}
                            className="block"
                        >
                            <Card className="stat-card hover:shadow-lg transition-all duration-200 cursor-pointer">
                                <CardContent className="p-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="flex-1 min-w-0">
                                            <h3 className="font-semibold text-base mb-2 line-clamp-2">
                                                {incident.title}
                                            </h3>
                                            <p className="text-sm text-muted-foreground mb-3 line-clamp-2">
                                                {incident.description}
                                            </p>
                                            
                                            <div className="flex items-center gap-4 text-xs text-muted-foreground mb-3">
                                                <div className="flex items-center gap-1">
                                                    <User className="h-3 w-3" />
                                                    <span>{incident.user.name}</span>
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <Building2 className="h-3 w-3" />
                                                    <span>{incident.branch.name}</span>
                                                </div>
                                            </div>
                                            
                                            <div className="flex items-center gap-3 text-xs">
                                                <div className="flex items-center gap-1">
                                                    <MessageSquare className="h-3 w-3" />
                                                    <span>{incident.comments_count}</span>
                                                </div>
                                                {incident.has_file && (
                                                    <div className="flex items-center gap-1 text-success">
                                                        <Paperclip className="h-3 w-3" />
                                                        <span>Archivo</span>
                                                    </div>
                                                )}
                                                <span className="text-muted-foreground ml-auto">
                                                    {new Date(incident.created_at).toLocaleDateString('es-MX')}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div className="flex flex-col gap-2 flex-shrink-0">
                                            <Badge className={`text-xs px-2 py-1 ${getStatusBadgeColor(incident.status)}`}>
                                                {getStatusLabel(incident.status)}
                                            </Badge>
                                            <Badge className={`text-xs px-2 py-1 ${getPriorityBadgeColor(incident.priority)}`}>
                                                {getPriorityLabel(incident.priority)}
                                            </Badge>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {/* Empty State */}
                {incidents.data.length === 0 && (
                    <Card className="text-center py-12">
                        <CardContent>
                            <AlertTriangle className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                            <h3 className="text-lg font-semibold mb-2">No hay incidencias</h3>
                            <p className="text-muted-foreground mb-4">
                                {filters.search || filters.status || filters.priority 
                                    ? 'No se encontraron incidencias con los filtros aplicados.'
                                    : 'Aún no hay incidencias registradas. Crea la primera incidencia.'
                                }
                            </p>
                            {(!filters.search && !filters.status && !filters.priority) && (
                                <Button asChild>
                                    <Link href="/incidents/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Nueva Incidencia
                                    </Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Pagination */}
                {incidents.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {incidents.current_page > 1 && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.get('/incidents', { 
                                    ...filters, 
                                    page: incidents.current_page - 1 
                                }, { preserveState: true })}
                            >
                                Anterior
                            </Button>
                        )}
                        
                        <span className="text-sm text-muted-foreground px-3">
                            Página {incidents.current_page} de {incidents.last_page}
                        </span>
                        
                        {incidents.current_page < incidents.last_page && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.get('/incidents', { 
                                    ...filters, 
                                    page: incidents.current_page + 1 
                                }, { preserveState: true })}
                            >
                                Siguiente
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}