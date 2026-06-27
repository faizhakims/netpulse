<?php

namespace App\Http\Controllers\Api;

use App\Models\Device;
use App\Models\DeviceStatus;
use App\Models\Incident;
use App\Models\AlertRule;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RdfController extends BaseApiController
{
    private const BASE_URI   = 'http://netpulse.local/resource/';
    private const ONTOLOGY   = 'http://netpulse.local/ontology#';
    private const SCHEMA     = 'https://schema.org/';
    private const XSD        = 'http://www.w3.org/2001/XMLSchema#';

    private function negotiate(Request $request): string
    {
        $accept = $request->header('Accept', '');

        if (str_contains($accept, 'text/turtle'))              return 'turtle';
        if (str_contains($accept, 'application/rdf+xml'))      return 'rdfxml';
        if (str_contains($accept, 'application/ld+json'))      return 'jsonld';
        if ($request->query('format') === 'turtle')            return 'turtle';
        if ($request->query('format') === 'rdfxml')            return 'rdfxml';

        return 'jsonld';
    }

    public function devices(Request $request): Response
    {
        $statuses = DeviceStatus::latestPerDevice();
        $format   = $this->negotiate($request);

        return match ($format) {
            'turtle' => $this->devicesAsTurtle($statuses),
            'rdfxml' => $this->devicesAsRdfXml($statuses),
            default  => $this->devicesAsJsonLd($statuses),
        };
    }

    public function device(Request $request, string $name): Response
    {
        $status = DeviceStatus::with('device')
            ->whereHas('device', fn($q) => $q->where('name', $name))
            ->latest('checked_at')
            ->first();

        if (!$status) {
            return response("Device '{$name}' not found.", 404);
        }

        $format = $this->negotiate($request);

        return match ($format) {
            'turtle' => $this->deviceAsTurtle($status),
            'rdfxml' => $this->deviceAsRdfXml($status),
            default  => $this->deviceAsJsonLd($status),
        };
    }

    public function incidents(Request $request): Response
    {
        $incidents = Incident::with('device')->latest('started_at')->limit(100)->get();
        $format    = $this->negotiate($request);

        return match ($format) {
            'turtle' => $this->incidentsAsTurtle($incidents),
            'rdfxml' => $this->incidentsAsRdfXml($incidents),
            default  => $this->incidentsAsJsonLd($incidents),
        };
    }

    public function ontology(): Response
    {
        $ttl = $this->buildOntologyTurtle();
        return response($ttl, 200, [
            'Content-Type'  => 'text/turtle; charset=UTF-8',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    private function deviceSlug(DeviceStatus $status): string
    {
        $name = $status->device->name ?? ('device-' . $status->device_id);
        return strtolower(preg_replace('/[^a-zA-Z0-9\-_]/', '-', $name));
    }

    private function deviceUri(DeviceStatus $status): string
    {
        return self::BASE_URI . 'device/' . $this->deviceSlug($status);
    }

    private function schemaOperatingStatus(string $effective): string
    {
        return match ($effective) {
            'up'    => self::SCHEMA . 'InStock',
            'down'  => self::SCHEMA . 'Discontinued',
            default => self::SCHEMA . 'LimitedAvailability',
        };
    }

    private function devicesAsJsonLd($statuses): Response
    {
        $graph = [];

        foreach ($statuses as $status) {
            $effective = $status->effectiveStatus();
            $device    = $status->device;

            $node = [
                '@id'             => $this->deviceUri($status),
                '@type'           => [self::SCHEMA . 'ComputerServer', self::ONTOLOGY . 'NetworkDevice'],
                self::SCHEMA . 'name'            => $device->name ?? 'Unknown',
                self::SCHEMA . 'description'     => ($device->type ?? 'Network Device') . ' at layer ' . ($device->layer ?? 'N/A'),
                self::SCHEMA . 'operatingStatus' => ['@id' => $this->schemaOperatingStatus($effective)],
                self::ONTOLOGY . 'ipAddress'     => $status->ip_address ?? '',
                self::ONTOLOGY . 'status'        => $effective,
                self::ONTOLOGY . 'layer'         => $device->layer ?? '',
                self::ONTOLOGY . 'deviceType'    => $device->type ?? '',
                self::ONTOLOGY . 'checkedAt'     => [
                    '@type'  => self::XSD . 'dateTime',
                    '@value' => $status->checked_at ? $status->checked_at->toIso8601String() : '',
                ],
            ];

            if ($status->latency_ms !== null) {
                $node[self::ONTOLOGY . 'latencyMs'] = [
                    '@type'  => self::XSD . 'decimal',
                    '@value' => (string) $status->latency_ms,
                ];
            }

            $graph[] = $node;
        }

        $payload = [
            '@context' => [
                'schema'    => self::SCHEMA,
                'netpulse'  => self::ONTOLOGY,
                'xsd'       => self::XSD,
            ],
            '@graph' => $graph,
        ];

        return response(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type'  => 'application/ld+json; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function deviceAsJsonLd(DeviceStatus $status): Response
    {
        $effective = $status->effectiveStatus();
        $device    = $status->device;

        $payload = [
            '@context' => [
                'schema'   => self::SCHEMA,
                'netpulse' => self::ONTOLOGY,
                'xsd'      => self::XSD,
            ],
            '@id'   => $this->deviceUri($status),
            '@type' => [self::SCHEMA . 'ComputerServer', self::ONTOLOGY . 'NetworkDevice'],
            self::SCHEMA . 'name'            => $device->name ?? 'Unknown',
            self::SCHEMA . 'description'     => ($device->type ?? 'Network Device') . ' at layer ' . ($device->layer ?? 'N/A'),
            self::SCHEMA . 'operatingStatus' => ['@id' => $this->schemaOperatingStatus($effective)],
            self::ONTOLOGY . 'ipAddress'     => $status->ip_address ?? '',
            self::ONTOLOGY . 'status'        => $effective,
            self::ONTOLOGY . 'layer'         => $device->layer ?? '',
            self::ONTOLOGY . 'deviceType'    => $device->type ?? '',
            self::ONTOLOGY . 'checkedAt'     => [
                '@type'  => self::XSD . 'dateTime',
                '@value' => $status->checked_at ? $status->checked_at->toIso8601String() : '',
            ],
            self::ONTOLOGY . 'latencyMs' => $status->latency_ms !== null ? [
                '@type'  => self::XSD . 'decimal',
                '@value' => (string) $status->latency_ms,
            ] : null,
        ];

        return response(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type'  => 'application/ld+json; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function devicesAsTurtle($statuses): Response
    {
        $lines = $this->turtlePrefixes();

        foreach ($statuses as $status) {
            $lines[] = '';
            $lines   = array_merge($lines, $this->deviceToTurtleTriples($status));
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type'  => 'text/turtle; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function deviceAsTurtle(DeviceStatus $status): Response
    {
        $lines = array_merge($this->turtlePrefixes(), [''], $this->deviceToTurtleTriples($status));

        return response(implode("\n", $lines), 200, [
            'Content-Type'  => 'text/turtle; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function deviceToTurtleTriples(DeviceStatus $status): array
    {
        $effective = $status->effectiveStatus();
        $device    = $status->device;
        $uri       = '<' . $this->deviceUri($status) . '>';
        $name      = addslashes($device->name ?? 'Unknown');
        $ip        = addslashes($status->ip_address ?? '');
        $layer     = addslashes($device->layer ?? '');
        $type      = addslashes($device->type ?? 'Network Device');
        $checkedAt = $status->checked_at ? $status->checked_at->toIso8601String() : '';

        $triples = [
            "{$uri}",
            "    a schema:ComputerServer, netpulse:NetworkDevice ;",
            "    schema:name \"{$name}\" ;",
            "    schema:description \"{$type} at layer {$layer}\" ;",
            "    schema:operatingStatus <" . $this->schemaOperatingStatus($effective) . "> ;",
            "    netpulse:ipAddress \"{$ip}\" ;",
            "    netpulse:status \"{$effective}\" ;",
            "    netpulse:layer \"{$layer}\" ;",
            "    netpulse:deviceType \"{$type}\" ;",
            "    netpulse:checkedAt \"{$checkedAt}\"^^xsd:dateTime ;",
        ];

        if ($status->latency_ms !== null) {
            $triples[] = "    netpulse:latencyMs \"{$status->latency_ms}\"^^xsd:decimal ;";
        }

        $last = array_pop($triples);
        $triples[] = rtrim($last, ' ;') . ' .';

        return $triples;
    }

    private function devicesAsRdfXml($statuses): Response
    {
        $items = '';
        foreach ($statuses as $status) {
            $items .= $this->deviceToRdfXmlDescription($status);
        }

        return response($this->wrapRdfXml($items), 200, [
            'Content-Type'  => 'application/rdf+xml; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function deviceAsRdfXml(DeviceStatus $status): Response
    {
        return response($this->wrapRdfXml($this->deviceToRdfXmlDescription($status)), 200, [
            'Content-Type'  => 'application/rdf+xml; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function deviceToRdfXmlDescription(DeviceStatus $status): string
    {
        $effective = $status->effectiveStatus();
        $device    = $status->device;
        $uri       = htmlspecialchars($this->deviceUri($status));
        $name      = htmlspecialchars($device->name ?? 'Unknown');
        $ip        = htmlspecialchars($status->ip_address ?? '');
        $layer     = htmlspecialchars($device->layer ?? '');
        $type      = htmlspecialchars($device->type ?? 'Network Device');
        $checkedAt = $status->checked_at ? $status->checked_at->toIso8601String() : '';
        $opStatus  = htmlspecialchars($this->schemaOperatingStatus($effective));

        $latencyTriple = $status->latency_ms !== null
            ? "    <netpulse:latencyMs rdf:datatype=\"http://www.w3.org/2001/XMLSchema#decimal\">{$status->latency_ms}</netpulse:latencyMs>\n"
            : '';

        return <<<XML
  <rdf:Description rdf:about="{$uri}">
    <rdf:type rdf:resource="https://schema.org/ComputerServer"/>
    <rdf:type rdf:resource="http://netpulse.local/ontology#NetworkDevice"/>
    <schema:name>{$name}</schema:name>
    <schema:description>{$type} at layer {$layer}</schema:description>
    <schema:operatingStatus rdf:resource="{$opStatus}"/>
    <netpulse:ipAddress>{$ip}</netpulse:ipAddress>
    <netpulse:status>{$effective}</netpulse:status>
    <netpulse:layer>{$layer}</netpulse:layer>
    <netpulse:deviceType>{$type}</netpulse:deviceType>
    <netpulse:checkedAt rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">{$checkedAt}</netpulse:checkedAt>
{$latencyTriple}  </rdf:Description>
XML;
    }

    private function incidentsAsJsonLd($incidents): Response
    {
        $graph = [];

        foreach ($incidents as $incident) {
            $uri = self::BASE_URI . 'incident/' . $incident->id;

            $node = [
                '@id'   => $uri,
                '@type' => [self::SCHEMA . 'Event', self::ONTOLOGY . 'NetworkIncident'],
                self::SCHEMA . 'name'        => $incident->issue ?? 'Network Incident',
                self::SCHEMA . 'description' => 'Status: ' . ($incident->status ?? 'unknown'),
                self::SCHEMA . 'startDate'   => [
                    '@type'  => self::XSD . 'dateTime',
                    '@value' => $incident->started_at ? $incident->started_at->toIso8601String() : '',
                ],
                self::ONTOLOGY . 'incidentStatus'   => $incident->status ?? 'unknown',
                self::ONTOLOGY . 'incidentDuration' => $incident->displayDuration(),
            ];

            if ($incident->resolved_at) {
                $node[self::SCHEMA . 'endDate'] = [
                    '@type'  => self::XSD . 'dateTime',
                    '@value' => $incident->resolved_at->toIso8601String(),
                ];
            }

            if ($incident->device) {
                $deviceStatus = DeviceStatus::with('device')
                    ->whereHas('device', fn($q) => $q->where('name', $incident->device->name))
                    ->latest('checked_at')
                    ->first();

                if ($deviceStatus) {
                    $node[self::ONTOLOGY . 'affectsDevice'] = ['@id' => $this->deviceUri($deviceStatus)];
                }
            }

            $graph[] = $node;
        }

        $payload = [
            '@context' => [
                'schema'   => self::SCHEMA,
                'netpulse' => self::ONTOLOGY,
                'xsd'      => self::XSD,
            ],
            '@graph' => $graph,
        ];

        return response(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, [
            'Content-Type'  => 'application/ld+json; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function incidentsAsTurtle($incidents): Response
    {
        $lines = $this->turtlePrefixes();

        foreach ($incidents as $incident) {
            $uri        = '<' . self::BASE_URI . 'incident/' . $incident->id . '>';
            $issue      = addslashes($incident->issue ?? 'Network Incident');
            $status     = addslashes($incident->status ?? 'unknown');
            $duration   = addslashes($incident->displayDuration());
            $startedAt  = $incident->started_at ? $incident->started_at->toIso8601String() : '';

            $lines[] = '';
            $lines[] = "{$uri}";
            $lines[] = "    a schema:Event, netpulse:NetworkIncident ;";
            $lines[] = "    schema:name \"{$issue}\" ;";
            $lines[] = "    netpulse:incidentStatus \"{$status}\" ;";
            $lines[] = "    netpulse:incidentDuration \"{$duration}\" ;";

            if ($startedAt) {
                $lines[] = "    schema:startDate \"{$startedAt}\"^^xsd:dateTime ;";
            }

            if ($incident->resolved_at) {
                $lines[] = "    schema:endDate \"{$incident->resolved_at->toIso8601String()}\"^^xsd:dateTime ;";
            }

            if ($incident->device) {
                $deviceStatus = DeviceStatus::with('device')
                    ->whereHas('device', fn($q) => $q->where('name', $incident->device->name))
                    ->latest('checked_at')
                    ->first();

                if ($deviceStatus) {
                    $lines[] = "    netpulse:affectsDevice <" . $this->deviceUri($deviceStatus) . "> ;";
                }
            }

            $last = array_pop($lines);
            $lines[] = rtrim($last, ' ;') . ' .';
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type'  => 'text/turtle; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function incidentsAsRdfXml($incidents): Response
    {
        $items = '';

        foreach ($incidents as $incident) {
            $uri      = htmlspecialchars(self::BASE_URI . 'incident/' . $incident->id);
            $issue    = htmlspecialchars($incident->issue ?? 'Network Incident');
            $status   = htmlspecialchars($incident->status ?? 'unknown');
            $duration = htmlspecialchars($incident->displayDuration());
            $start    = $incident->started_at ? $incident->started_at->toIso8601String() : '';
            $end      = $incident->resolved_at ? $incident->resolved_at->toIso8601String() : null;

            $deviceTriple = '';
            if ($incident->device) {
                $deviceStatus = DeviceStatus::with('device')
                    ->whereHas('device', fn($q) => $q->where('name', $incident->device->name))
                    ->latest('checked_at')
                    ->first();

                if ($deviceStatus) {
                    $dUri = htmlspecialchars($this->deviceUri($deviceStatus));
                    $deviceTriple = "    <netpulse:affectsDevice rdf:resource=\"{$dUri}\"/>\n";
                }
            }

            $endTriple = $end ? "    <schema:endDate rdf:datatype=\"http://www.w3.org/2001/XMLSchema#dateTime\">{$end}</schema:endDate>\n" : '';

            $items .= <<<XML
  <rdf:Description rdf:about="{$uri}">
    <rdf:type rdf:resource="https://schema.org/Event"/>
    <rdf:type rdf:resource="http://netpulse.local/ontology#NetworkIncident"/>
    <schema:name>{$issue}</schema:name>
    <netpulse:incidentStatus>{$status}</netpulse:incidentStatus>
    <netpulse:incidentDuration>{$duration}</netpulse:incidentDuration>
    <schema:startDate rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime">{$start}</schema:startDate>
{$endTriple}{$deviceTriple}  </rdf:Description>
XML;
        }

        return response($this->wrapRdfXml($items), 200, [
            'Content-Type'  => 'application/rdf+xml; charset=UTF-8',
            'Cache-Control' => 'no-store',
        ]);
    }

    private function turtlePrefixes(): array
    {
        return [
            '@prefix rdf:      <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .',
            '@prefix rdfs:     <http://www.w3.org/2000/01/rdf-schema#> .',
            '@prefix owl:      <http://www.w3.org/2002/07/owl#> .',
            '@prefix xsd:      <http://www.w3.org/2001/XMLSchema#> .',
            '@prefix schema:   <https://schema.org/> .',
            '@prefix netpulse: <' . self::ONTOLOGY . '> .',
        ];
    }

    private function wrapRdfXml(string $content): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rdf:RDF
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
    xmlns:owl="http://www.w3.org/2002/07/owl#"
    xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
    xmlns:schema="https://schema.org/"
    xmlns:netpulse="http://netpulse.local/ontology#">
{$content}
</rdf:RDF>
XML;
    }

    private function buildOntologyTurtle(): string
    {
        return <<<TURTLE
@prefix rdf:      <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix rdfs:     <http://www.w3.org/2000/01/rdf-schema#> .
@prefix owl:      <http://www.w3.org/2002/07/owl#> .
@prefix xsd:      <http://www.w3.org/2001/XMLSchema#> .
@prefix schema:   <https://schema.org/> .
@prefix netpulse: <http://netpulse.local/ontology#> .

<http://netpulse.local/ontology>
    a owl:Ontology ;
    rdfs:label "NetPulse Network Monitoring Ontology" ;
    rdfs:comment "Ontology describing network devices, incidents, and monitoring metrics for the NetPulse NOC system." .

netpulse:NetworkDevice
    a owl:Class ;
    rdfs:subClassOf schema:ComputerServer ;
    rdfs:label "Network Device" ;
    rdfs:comment "A physical or virtual device in the monitored network (router, switch, server, etc.)." .

netpulse:NetworkIncident
    a owl:Class ;
    rdfs:subClassOf schema:Event ;
    rdfs:label "Network Incident" ;
    rdfs:comment "A recorded network outage or degradation event affecting one or more devices." .

netpulse:ipAddress
    a owl:DatatypeProperty ;
    rdfs:domain netpulse:NetworkDevice ;
    rdfs:range xsd:string ;
    rdfs:label "IP Address" ;
    rdfs:comment "IPv4 or IPv6 address of the network device." .

netpulse:status
    a owl:DatatypeProperty ;
    rdfs:domain netpulse:NetworkDevice ;
    rdfs:range xsd:string ;
    rdfs:label "Status" ;
    rdfs:comment "Current effective status of the device: up, down, or unknown." .

netpulse:layer
    a owl:DatatypeProperty ;
    rdfs:domain netpulse:NetworkDevice ;
    rdfs:range xsd:string ;
    rdfs:label "Network Layer" ;
    rdfs:comment "OSI or network topology layer the device operates in." .

netpulse:deviceType
    a owl:DatatypeProperty ;
    rdfs:domain netpulse:NetworkDevice ;
    rdfs:range xsd:string ;
    rdfs:label "Device Type" ;
    rdfs:comment "Type of the network device, e.g. router, switch, server." .

netpulse:latencyMs
    a owl:DatatypeProperty ;
    rdfs:domain netpulse:NetworkDevice ;
    rdfs:range xsd:decimal ;
    rdfs:label "Latency (ms)" ;
    rdfs:comment "Round-trip latency to the device in milliseconds." .

netpulse:checkedAt
    a owl:DatatypeProperty ;
    rdfs:domain netpulse:NetworkDevice ;
    rdfs:range xsd:dateTime ;
    rdfs:label "Last Checked At" ;
    rdfs:comment "ISO 8601 timestamp of the most recent status check." .

netpulse:affectsDevice
    a owl:ObjectProperty ;
    rdfs:domain netpulse:NetworkIncident ;
    rdfs:range netpulse:NetworkDevice ;
    rdfs:label "Affects Device" ;
    rdfs:comment "Links an incident to the network device it affects." .

netpulse:incidentStatus
    a owl:DatatypeProperty ;
    rdfs:domain netpulse:NetworkIncident ;
    rdfs:range xsd:string ;
    rdfs:label "Incident Status" ;
    rdfs:comment "Severity or state of the incident: Critical, Warning, Monitoring." .

netpulse:incidentDuration
    a owl:DatatypeProperty ;
    rdfs:domain netpulse:NetworkIncident ;
    rdfs:range xsd:string ;
    rdfs:label "Incident Duration" ;
    rdfs:comment "Human-readable duration of the incident." .
TURTLE;
    }
}
