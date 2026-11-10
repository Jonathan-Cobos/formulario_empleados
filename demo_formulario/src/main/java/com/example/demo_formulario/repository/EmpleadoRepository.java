package com.example.demo_formulario.repository;

import org.springframework.data.jpa.repository.JpaRepository;

import com.example.demo_formulario.model.Empleado;

public interface EmpleadoRepository extends JpaRepository<Empleado, Integer> {
}
