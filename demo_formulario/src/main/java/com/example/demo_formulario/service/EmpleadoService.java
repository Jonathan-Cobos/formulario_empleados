package com.example.demo_formulario.service;

import java.util.LinkedHashSet;
import java.util.List;
import java.util.Set;
import java.util.stream.Collectors;

import org.springframework.http.HttpStatus;
import org.springframework.stereotype.Service;
import org.springframework.web.server.ResponseStatusException;

import com.example.demo_formulario.model.Area;
import com.example.demo_formulario.model.Empleado;
import com.example.demo_formulario.model.Rol;
import com.example.demo_formulario.repository.AreaRepository;
import com.example.demo_formulario.repository.EmpleadoRepository;
import com.example.demo_formulario.repository.RolRepository;

@Service
public class EmpleadoService {

    private final EmpleadoRepository empleadoRepository;
    private final AreaRepository areaRepository;
    private final RolRepository rolRepository;

    public EmpleadoService(
            EmpleadoRepository empleadoRepository,
            AreaRepository areaRepository,
            RolRepository rolRepository) {
        this.empleadoRepository = empleadoRepository;
        this.areaRepository = areaRepository;
        this.rolRepository = rolRepository;
    }

    public List<Empleado> findAll() {
        return empleadoRepository.findAll();
    }

    public Empleado findById(Integer id) {
        return empleadoRepository.findById(id)
                .orElseThrow(() -> new ResponseStatusException(HttpStatus.NOT_FOUND, "Empleado no encontrado"));
    }

    public Empleado save(Empleado empleado) {
        if (empleado.getArea() != null) {
            Integer areaId = empleado.getArea().getId();
            if (areaId == null) {
                throw new ResponseStatusException(HttpStatus.BAD_REQUEST, "El área debe incluir un id existente");
            }
            Area area = areaRepository.findById(areaId)
                    .orElseThrow(() -> new ResponseStatusException(HttpStatus.BAD_REQUEST, "Área no encontrada"));
            empleado.setArea(area);
        }

        Set<Rol> roles = empleado.getRoles().stream()
                .map(rol -> {
                    if (rol.getId() == null) {
                        throw new ResponseStatusException(HttpStatus.BAD_REQUEST, "Cada rol debe incluir un id existente");
                    }
                    return rolRepository.findById(rol.getId())
                            .orElseThrow(() -> new ResponseStatusException(HttpStatus.BAD_REQUEST, "Rol no encontrado"));
                })
                .collect(Collectors.toCollection(LinkedHashSet::new));
        empleado.setRoles(roles);
        return empleadoRepository.save(empleado);
    }

    public void delete(Integer id) {
        empleadoRepository.delete(findById(id));
    }
}
