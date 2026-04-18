<x-filament-panels::page>
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Organigramme de l'Hôpital
            </h2>

            {{-- Légende --}}
            <div class="flex gap-3 text-xs">
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 rounded" style="background: #3b82f6;"></div>
                    <span>Direction</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 rounded" style="background: #f59e0b;"></div>
                    <span>Sous-Direction</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 rounded" style="background: #10b981;"></div>
                    <span>Département</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 rounded" style="background: #8b5cf6;"></div>
                    <span>Service Admin</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-4 h-4 rounded" style="background: #06b6d4;"></div>
                    <span>Service Médical</span>
                </div>
            </div>
        </div>

        <div id="organigramme" class="w-full rounded-lg border border-gray-200 dark:border-gray-700" style="min-height: 600px;"></div>
    </div>

    @push('scripts')
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script>
        const orgData = @json($orgData ?? []);

        document.addEventListener('DOMContentLoaded', function() {
            if (orgData && orgData.name) {
                drawOrgChart(orgData);
            }
        });

        function drawOrgChart(data) {
            const container = document.getElementById('organigramme');
            const width = container.offsetWidth || 1200;
            const height = 800;

            container.innerHTML = '';

            const svg = d3.select("#organigramme")
                .append("svg")
                .attr("width", width)
                .attr("height", height)
                .style("background", "#fafafa");

            const g = svg.append("g")
                .attr("transform", "translate(50,50)");

            const root = d3.hierarchy(data);
            const treeLayout = d3.tree().size([height - 100, width - 200]);
            treeLayout(root);

            // Liens
            g.selectAll('.link')
                .data(root.links())
                .enter()
                .append('path')
                .attr('class', 'link')
                .attr('d', d3.linkHorizontal()
                    .x(d => d.y)
                    .y(d => d.x))
                .attr('fill', 'none')
                .attr('stroke', '#94a3b8')
                .attr('stroke-width', 2);

            // Nœuds
            const nodes = g.selectAll('.node')
                .data(root.descendants())
                .enter()
                .append('g')
                .attr('class', 'node')
                .attr('transform', d => `translate(${d.y},${d.x})`);

            nodes.append('rect')
                .attr('x', -60)
                .attr('y', -20)
                .attr('width', 120)
                .attr('height', 40)
                .attr('rx', 5)
                .attr('fill', d => {
                    const colors = {
                        'root': '#1e293b',
                        'direction': '#3b82f6',
                        'sub_direction': '#f59e0b',
                        'department': '#10b981',
                        'service': '#8b5cf6',
                        'service_medical': '#06b6d4'
                    };
                    return colors[d.data.type] || '#6b7280';
                })
                .attr('stroke', '#fff')
                .attr('stroke-width', 2);

            nodes.append('text')
                .attr('dy', 5)
                .attr('text-anchor', 'middle')
                .style('font-size', '10px')
                .style('font-weight', 'bold')
                .style('fill', '#fff')
                .text(d => {
                    const name = d.data.title || d.data.name || '';
                    return name.length > 12 ? name.substring(0, 12) + '...' : name;
                })
                .append('title')
                .text(d => (d.data.name || '') + (d.data.head ? '\n' + d.data.head : '') + (d.data.director ? '\n' + d.data.director : ''));
        }
    </script>

    <style>
        #organigramme {
            overflow-x: auto;
        }

        .node rect {
            cursor: pointer;
            transition: all 0.3s;
        }

        .node rect:hover {
            filter: brightness(1.2);
            stroke-width: 3px;
        }

        .link {
            transition: stroke 0.3s;
        }

        .link:hover {
            stroke: #3b82f6;
            stroke-width: 3px;
        }
    </style>
    @endpush
</x-filament-panels::page>